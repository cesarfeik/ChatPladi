<?php
/**
 * PLADIEX Chatbot — Endpoint de Chat
 * ===================================
 * Flujo:
 *  1. Recibe el mensaje del usuario (JSON POST)
 *  2. Crea un embedding del mensaje con OpenAI
 *  3. Busca fragmentos relevantes en Pinecone (RAG)
 *  4. Construye el prompt con el contexto encontrado
 *  5. Llama a GPT-4o-mini y devuelve la respuesta
 *
 * Método:  POST
 * Body:    { "message": "string", "history": [ {role, content}, ... ] }
 * Retorna: { "reply": "string", "links": [ {label, url}, ... ] }
 */

require_once __DIR__ . '/../config.php';

// ─── CORS ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// ─── Leer cuerpo de la petición ────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje vacío']);
    exit;
}

$userMessage = trim($body['message']);
$history     = $body['history'] ?? [];
$section     = trim($body['section'] ?? 'default');

// ─── 1. Crear embedding del mensaje ────────────────────────────────────────
$embedding = createEmbedding($userMessage);

if (!$embedding) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear embedding']);
    exit;
}

// ─── 2. Buscar contexto en Pinecone ────────────────────────────────────────
$context = queryPinecone($embedding, $section);

// ─── 3. Detectar si el usuario quiere navegar a otra sección ───────────────
$links = detectNavigationLinks($userMessage);

// ─── 4. Llamar a GPT-4o-mini con el contexto ───────────────────────────────
$reply = callChatGPT($userMessage, $history, $context, $section);

// ─── 5. Enviar respuesta al cliente ────────────────────────────────────────
$responseJson = json_encode(['reply' => $reply, 'links' => $links]);

// Cerrar la conexión HTTP para que el navegador reciba la respuesta
// mientras el servidor continúa indexando la conversación en background.
header('Content-Length: ' . strlen($responseJson));
header('Connection: close');
echo $responseJson;

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // Vercel / PHP-FPM
} else {
    ob_flush();
    flush();
}

// ─── 6. Indexar par pregunta+respuesta en Pinecone (background) ────────────
ignore_user_abort(true);
indexConversation($userMessage, $reply, $section);


// ═══════════════════════════════════════════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════════════════════════════════════════

function createEmbedding(string $text): ?array
{
    $response = httpPost(
        'https://api.openai.com/v1/embeddings',
        ['Authorization' => 'Bearer ' . OPENAI_API_KEY],
        [
            'model' => OPENAI_EMBED_MODEL,
            'input' => $text,
        ]
    );

    return $response['data'][0]['embedding'] ?? null;
}

function queryPinecone(array $vector, string $section = 'default'): string
{
    $body = [
        'vector'          => $vector,
        'topK'            => RAG_TOP_K,
        'includeMetadata' => true,
        'namespace'       => PINECONE_NAMESPACE,
    ];

    // Filtrar por sección: busca docs etiquetados con la sección activa
    // O documentos con "all" (aplican a todas las secciones).
    // Si es "default" no filtra para mantener compatibilidad hacia atrás.
    if ($section !== 'default') {
        $body['filter'] = [
            'sections' => ['$in' => [$section, 'all']],
        ];
    }

    $response = httpPost(
        PINECONE_INDEX_HOST . '/query',
        [
            'Api-Key'      => PINECONE_API_KEY,
            'Content-Type' => 'application/json',
        ],
        $body
    );

    if (empty($response['matches'])) {
        return '';
    }

    $chunks = [];
    foreach ($response['matches'] as $match) {
        if (!empty($match['metadata']['text'])) {
            $chunks[] = $match['metadata']['text'];
        }
    }

    return implode("\n\n---\n\n", $chunks);
}

function buildSystemPrompt(string $section, string $context): string
{
    // ── Reglas base compartidas por todos los prompts ──────────────────────
    $baseRules = <<<RULES

Reglas de comportamiento (aplican siempre):
1. Responde siempre en español, de forma clara, empática y profesional.
2. Para síntomas, ofrece orientación general y SIEMPRE recomienda consultar a un médico.
3. NUNCA des diagnósticos definitivos ni recetas médicas.
4. Si el usuario quiere navegar a otra sección, indícale que verá un botón de acceso directo.
5. Usa solo la información del contexto proporcionado; si no sabes algo, dilo honestamente.
6. Respuestas cortas y directas (máximo 3 párrafos salvo que el usuario pida más detalle).

Contexto de la base de conocimiento:
{$context}
RULES;

    // ── Prompts especializados por sección ─────────────────────────────────
    $prompts = [

        'home' => <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, una plataforma de salud digital en México y Latinoamérica.
Estás en la PÁGINA PRINCIPAL del sitio. Tu misión es dar la bienvenida, explicar el ecosistema PLADIEX y
guiar al usuario hacia la sección que más le conviene según sus necesidades.

Lo que debes conocer a profundidad:
- PLADIEX conecta pacientes, médicos, laboratorios, farmacias y aseguradoras en un solo lugar.
- Cifras clave: +1,500 médicos integrados, +5,000 pacientes, +900 clínicas aliadas, +$1,000,000 MXN en créditos otorgados.
- Secciones principales: Servicios (qué ofrecemos), Financiamiento (crédito médico), Tienda (PLADIEX Mall), Contacto.
- Pre-diagnóstico con IA disponible para pacientes.
- Registro disponible para pacientes y médicos.
PROMPT,

        'financiamiento' => <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, especialista en FINANCIAMIENTO MÉDICO.
Estás en la página de Financiamiento. Tu misión es explicar el crédito médico de PLADIEX,
ayudar al usuario a entender los planes, plazos y proceso de solicitud.

Lo que debes conocer a profundidad:
- PLADIEX ofrece financiamiento para estudios, cirugías y tratamientos médicos.
- Montos disponibles: desde $5,000 hasta $500,000 MXN.
- Segmentos y tasas anuales:
  • Segmento A ($5,000–$50,000):     35% | plazos: 3, 6, 9, 12 meses
  • Segmento B ($50,001–$150,000):   30% | plazos: 6, 12, 18, 24 meses
  • Segmento C ($150,001–$300,000):  25% | plazos: 12, 24, 36, 48 meses
  • Segmento D ($300,001–$500,000):  20% | plazos: 12, 24, 36, 48 meses
- Proceso: Simula → Solicita → Elige Médico → Programa → PLADIEX paga al médico.
- Para simular pagos: usa la calculadora en la página o pregúntame y te ayudo a calcular.
- Registro en: https://pladiex.com/landing-registro/
PROMPT,

        'servicios' => <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, especialista en los SERVICIOS Y SOLUCIONES de la plataforma.
Estás en la página "Ofrecemos". Tu misión es explicar la propuesta de valor de PLADIEX
según el tipo de usuario (paciente, médico, asociación o universidad).

Lo que debes conocer a profundidad:

PARA PACIENTES:
- Encuentra médicos, laboratorios, farmacias y más en un solo lugar.
- Agenda consultas presenciales o en línea.
- Accede a financiamiento médico.
- Recibe recordatorios personalizados.
- Guarda tu expediente médico digital.
- Obtén pre-diagnóstico con inteligencia artificial.

PARA MÉDICOS:
- Plataforma digital para consultas presenciales y telemedicina.
- Herramientas para administrar expedientes, agenda y estadísticas.
- Espacio para ofrecer productos, servicios y eventos.
- Genera ingresos por membresías, comisiones y campañas.
- Promoción personalizada de tu consultorio.

PARA ASOCIACIONES MÉDICAS:
- Espacios para integrar servicios de sus agremiados.
- Alianzas estratégicas con PLADIEX.
- Campañas de concientización en salud.
- Plataforma para eventos y beneficios para socios.

PARA UNIVERSIDADES:
- Herramientas digitales para prácticas clínicas virtuales.
- Integración de estudiantes y egresados a la red médica.
- Colaboración en proyectos de investigación.
- Vinculación laboral con clínicas aliadas.

Registro disponible en: https://pladiex.com/sistema/index.html
PROMPT,

        'tienda' => <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, especialista en el PLADIEX MALL (tienda médica).
Estás en la Tienda. Tu misión es ayudar al usuario a encontrar productos médicos,
entender las categorías disponibles y orientarle en su compra.

Lo que debes conocer a profundidad:
- PLADIEX Mall es el marketplace de productos y servicios médicos de PLADIEX.
- Categorías principales disponibles:
  • Equipos Médicos Diagnósticos: estetoscopios, tensiómetros, glucómetros, ECG, monitores, etc.
  • Instrumental Quirúrgico y Clínico: pinzas, tijeras, bisturís, kits de sutura, instrumental odontológico.
  • Ropa y Accesorios Profesionales: batas, mascarillas N95, guantes, calzado antibacterial, maletines.
  • Mobiliario Clínico: camillas, sillas ginecológicas, escritorios ergonómicos, carros de curaciones.
  • Tecnología y Software Médico: software HCE, sistemas de citas, apps de diagnóstico, telemedicina.
  • Insumos y Consumibles: gasas, antisépticos, solución salina, kits de emergencia.
  • Formación y Educación Médica: libros, cursos online, simuladores, conferencias.
  • Productos por Especialidad: cardiología, dermatología, ginecología, oftalmología, pediatría, etc.
- Los productos pueden financiarse con crédito médico PLADIEX.
- Para comprar, el usuario puede registrarse en: https://pladiex.com/sistema/index.html
PROMPT,

        'contacto' => <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, especialista en ATENCIÓN AL CLIENTE Y CONTACTO.
Estás en la página de Contacto. Tu misión es orientar al usuario sobre los canales de comunicación
disponibles, horarios de atención y cómo resolver sus dudas rápidamente.

Lo que debes conocer a profundidad:
- Teléfono directo: 56 3231 1545
- Email: hola@pladiex.mx
- WhatsApp: +52 56 3231 1545 (enlace: https://api.whatsapp.com/send?phone=+525632311545)
- Horario de atención: disponibles 24/7 para soporte digital; atención personalizada lunes–viernes 8:00–20:00.
- Formulario de contacto disponible en la página (campos: Nombre, Email, Asunto, Mensaje).
- Para agendar reunión con el equipo comercial: usar el formulario o escribir al email/WhatsApp.
- Misión PLADIEX: "Transformar el acceso a la salud en México y Latinoamérica".
- Redes sociales: Facebook, Twitter/X, LinkedIn, Instagram.
PROMPT,

    ];

    // ── Seleccionar prompt o usar el default general ───────────────────────
    $intro = $prompts[$section] ?? <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, una plataforma de salud digital en México y Latinoamérica.
Tu misión es ayudar a los usuarios con:
- Preguntas sobre la plataforma (servicios, registro, citas, tienda, etc.)
- Pre-diagnóstico orientativo basado en síntomas que el usuario describe
- Canalización hacia el especialista médico adecuado
PROMPT;

    return $intro . $baseRules;
}

function callChatGPT(string $userMessage, array $history, string $context, string $section = 'default'): string
{
    $systemPrompt = buildSystemPrompt($section, $context);

    $messages = [['role' => 'system', 'content' => $systemPrompt]];

    $recentHistory = array_slice($history, -10);
    foreach ($recentHistory as $turn) {
        if (!empty($turn['role']) && !empty($turn['content'])) {
            $messages[] = [
                'role'    => in_array($turn['role'], ['user', 'assistant']) ? $turn['role'] : 'user',
                'content' => $turn['content'],
            ];
        }
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    $response = httpPost(
        'https://api.openai.com/v1/chat/completions',
        ['Authorization' => 'Bearer ' . OPENAI_API_KEY],
        [
            'model'       => OPENAI_CHAT_MODEL,
            'messages'    => $messages,
            'temperature' => 0.4,
            'max_tokens'  => 600,
        ]
    );

    return $response['choices'][0]['message']['content']
        ?? 'Lo siento, ocurrió un error. Por favor intenta de nuevo.';
}

function detectNavigationLinks(string $message): array
{
    $message = mb_strtolower($message);
    $links   = [];

    $map = [
        ['keywords' => ['tienda', 'comprar', 'producto'],
         'label'    => 'Ir a la Tienda',
         'url'      => 'https://pladiex.com/mall/',
         'color'    => '#E7BA11'],

        ['keywords' => ['cita', 'consulta', 'agendar', 'médico'],
         'label'    => 'Ver Médicos / Citas',
         'url'      => 'https://pladiex.com/sistema/index.html',
         'color'    => '#5CB3C1'],

        ['keywords' => ['perfil', 'mi cuenta', 'iniciar sesión'],
         'label'    => 'Mi Perfil',
         'url'      => 'https://pladiex.com/sistema/index.html',
         'color'    => '#01587A'],

        ['keywords' => ['préstamo', 'financiamiento', 'crédito'],
         'label'    => 'Financiamiento',
         'url'      => 'https://pladiex.com/landing-registro/',
         'color'    => '#E7BA11'],

        ['keywords' => ['capacitación', 'curso', 'formación'],
         'label'    => 'Capacitación',
         'url'      => 'https://pladiex.com/plataforma/cursos.html',
         'color'    => '#5CB3C1'],

        ['keywords' => ['contacto', 'whatsapp', 'llamar'],
         'label'    => 'Contacto',
         'url'      => 'https://api.whatsapp.com/send?phone=+525632311545&text=¡Bienvenido%20a%20PLADIEX!%20¿En%20qué%20podemos%20ayudarte?',
         'color'    => '#01587A'],
    ];

    foreach ($map as $item) {
        foreach ($item['keywords'] as $kw) {
            if (str_contains($message, $kw)) {
                $links[] = ['label' => $item['label'], 'url' => $item['url'], 'color' => $item['color']];
                break;
            }
        }
    }

    return $links;
}

// ─── Indexar conversación en Pinecone ──────────────────────────────────────
function indexConversation(string $question, string $answer, string $section): void
{
    // Texto combinado que se embebdrá: la pregunta guía la búsqueda semántica
    // y la respuesta enriquece el contexto que verá el bot en futuras consultas.
    $combinedText = "Pregunta: {$question}\nRespuesta: {$answer}";

    // Descartar intercambios demasiado cortos o triviales
    if (mb_strlen(trim($answer)) < 30) return;

    $embedding = createEmbedding($question); // Embebe la PREGUNTA para búsqueda semántica
    if (!$embedding) return;

    // ID único: sección + hash del texto + timestamp
    $id = 'conv_' . preg_replace('/[^a-z0-9]/', '_', $section)
        . '_' . substr(md5($combinedText), 0, 12)
        . '_' . time();

    $vector = [
        'id'       => $id,
        'values'   => $embedding,
        'metadata' => [
            'text'      => $combinedText,   // Contexto que verá el bot
            'question'  => $question,
            'answer'    => $answer,
            'section'   => $section,
            'source'    => 'conversation',  // Distingue de PDFs subidos
            'timestamp' => date('c'),
        ],
    ];

    $result = httpPost(
        PINECONE_INDEX_HOST . '/vectors/upsert',
        ['Api-Key' => PINECONE_API_KEY],
        ['vectors' => [$vector], 'namespace' => PINECONE_NAMESPACE]
    );

    if (empty($result)) {
        error_log("PLADIEX chatbot — fallo al indexar conversación: {$id}");
    }
}

function httpPost(string $url, array $headers, array $data): array
{
    $ch = curl_init($url);

    $headerLines = ['Content-Type: application/json'];
    foreach ($headers as $key => $value) {
        if ($key !== 'Content-Type') {
            $headerLines[] = "{$key}: {$value}";
        }
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => $headerLines,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $httpCode >= 400) {
        error_log("PLADIEX chatbot — error en {$url}: HTTP {$httpCode}");
        return [];
    }

    return json_decode($raw, true) ?? [];
}

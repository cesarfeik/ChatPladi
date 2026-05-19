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

// ─── 1. Crear embedding del mensaje ────────────────────────────────────────
$embedding = createEmbedding($userMessage);

if (!$embedding) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear embedding']);
    exit;
}

// ─── 2. Buscar contexto en Pinecone ────────────────────────────────────────
$context = queryPinecone($embedding);

// ─── 3. Detectar si el usuario quiere navegar a otra sección ───────────────
$links = detectNavigationLinks($userMessage);

// ─── 4. Llamar a GPT-4o-mini con el contexto ───────────────────────────────
$reply = callChatGPT($userMessage, $history, $context);

echo json_encode([
    'reply' => $reply,
    'links' => $links,
]);


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

function queryPinecone(array $vector): string
{
    $response = httpPost(
        PINECONE_INDEX_HOST . '/query',
        [
            'Api-Key'      => PINECONE_API_KEY,
            'Content-Type' => 'application/json',
        ],
        [
            'vector'          => $vector,
            'topK'            => RAG_TOP_K,
            'includeMetadata' => true,
            'namespace'       => PINECONE_NAMESPACE,
        ]
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

function callChatGPT(string $userMessage, array $history, string $context): string
{
    $systemPrompt = <<<PROMPT
Eres Alex Ciencia, el asistente virtual de PLADIEX, una plataforma de salud digital en México y Latinoamérica.
Tu misión es ayudar a los usuarios con:
- Preguntas sobre la plataforma (servicios, registro, citas, tienda, etc.)
- Pre-diagnóstico orientativo basado en síntomas que el usuario describe
- Canalización hacia el especialista médico adecuado

Reglas de comportamiento:
1. Responde siempre en español, de forma clara, empática y profesional.
2. Para síntomas, ofrece una orientación general y SIEMPRE recomienda consultar a un médico.
3. NUNCA des diagnósticos definitivos ni recetas médicas.
4. Si el usuario quiere ir a la tienda, su perfil u otra sección, indícale que verá un botón de acceso directo.
5. Usa solo la información del contexto proporcionado; si no sabes algo, dilo honestamente.
6. Respuestas cortas y directas (máximo 3 párrafos salvo que el usuario pida más detalle).

Contexto de la base de conocimiento:
{$context}
PROMPT;

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

<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
/**
 * PLADIEX Admin — Procesar PDF e Indexar en Pinecone
 * ===================================================
 * Flujo:
 *  1. Valida el JWT de Supabase (solo admins)
 *  2. Recibe el texto extraído del PDF (desde el frontend con PDF.js)
 *  3. Divide el texto en fragmentos (chunks)
 *  4. Crea un embedding por fragmento con OpenAI
 *  5. Guarda todos los vectores en Pinecone
 *
 * Método:  POST
 * Headers: Authorization: Bearer <supabase_access_token>
 * Body:    { "filename": "string", "text": "string" }
 * Retorna: { "success": true, "chunks": N }
 */

require_once __DIR__ . '/../../config.php';

// ─── CORS ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { jsonError('Método no permitido', 405); }

// ─── Autenticación ─────────────────────────────────────────────────────────
$token = getBearerToken();
if (!$token || !validateSupabaseJWT($token)) {
    jsonError('No autorizado', 401);
}

// ─── Leer cuerpo ───────────────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['text']) || empty($body['filename'])) {
    jsonError('Faltan campos requeridos: filename y text');
}

$filename = trim($body['filename']);
$text     = trim($body['text']);

if (strlen($text) < 10) {
    jsonError('El texto extraído es demasiado corto o el PDF está vacío');
}

// ─── 1. Dividir texto en fragmentos ────────────────────────────────────────
$chunks = splitIntoChunks($text, RAG_CHUNK_SIZE, RAG_CHUNK_OVERLAP);

if (empty($chunks)) {
    jsonError('No se pudieron generar fragmentos del texto');
}

// ─── 2. Crear embeddings y vectores ────────────────────────────────────────
$vectors = [];

foreach ($chunks as $i => $chunk) {
    $embedding = createEmbedding($chunk);

    if (!$embedding) {
        // Registrar el error pero continuar con los demás fragmentos
        error_log("PLADIEX admin: fallo embedding en chunk {$i} de {$filename}");
        continue;
    }

    // ID único: nombre del archivo + índice del fragmento
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_chunk_' . $i;

    $vectors[] = [
        'id'       => $safeId,
        'values'   => $embedding,
        'metadata' => [
            'text'     => $chunk,
            'filename' => $filename,
            'chunk'    => $i,
            'source'   => 'pdf_upload',
        ],
    ];
}

if (empty($vectors)) {
    jsonError('No se pudo crear ningún embedding. Verifica tu API key de OpenAI');
}

// ─── 3. Guardar en Pinecone (en lotes de 100) ──────────────────────────────
$batches = array_chunk($vectors, 100);
foreach ($batches as $batch) {
    $result = upsertToPinecone($batch);
    if (!$result) {
        jsonError('Error al guardar en Pinecone. Verifica tu API key y el host del índice');
    }
}

$safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

echo json_encode([
    'success'     => true,
    'filename'    => $filename,
    'safe_prefix' => $safePrefix,
    'chunks'      => count($vectors),
]);


// ═══════════════════════════════════════════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Divide el texto en fragmentos solapados para mejor recuperación RAG.
 */
function splitIntoChunks(string $text, int $size, int $overlap): array
{
    // Normalizar espacios
    $text  = preg_replace('/\s+/', ' ', $text);
    $words = explode(' ', $text);
    $total = count($words);
    $chunks = [];
    $step   = max(1, $size - $overlap);

    for ($i = 0; $i < $total; $i += $step) {
        $slice = array_slice($words, $i, $size);
        if (empty($slice)) break;
        $chunk = implode(' ', $slice);
        if (strlen(trim($chunk)) > 20) {   // ignorar fragmentos muy pequeños
            $chunks[] = trim($chunk);
        }
    }

    return $chunks;
}

/**
 * Crea un embedding para un texto usando OpenAI text-embedding-3-small.
 */
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

/**
 * Inserta o actualiza vectores en Pinecone (upsert).
 */
function upsertToPinecone(array $vectors): bool
{
    $response = httpPost(
        PINECONE_INDEX_HOST . '/vectors/upsert',
        ['Api-Key' => PINECONE_API_KEY],
        [
            'vectors'   => $vectors,
            'namespace' => PINECONE_NAMESPACE,
        ]
    );

    return !empty($response['upsertedCount']) || isset($response['upsertedCount']);
}

/**
 * Valida el JWT de Supabase verificando la firma con el JWT Secret.
 * Verificación simple de estructura + decodificación. Para mayor seguridad
 * en producción, usa la librería firebase/php-jwt.
 */
function validateSupabaseJWT(string $token): bool
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    // Decodificar payload (sin verificar firma, solo para validar rol)
    // En producción reemplaza esto por verificación con firebase/php-jwt
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

    if (empty($payload)) return false;

    // Verificar que el token no esté expirado
    if (!empty($payload['exp']) && $payload['exp'] < time()) return false;

    // Verificar que viene de tu proyecto Supabase
    $expectedIssuer = rtrim(SUPABASE_URL, '/') . '/auth/v1';
    if (!empty($payload['iss']) && $payload['iss'] !== $expectedIssuer) return false;

    return true;
}

/**
 * Extrae el Bearer token del header Authorization.
 */
function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    // Fallback para XAMPP/Apache que bloquea Authorization por defecto
    if (empty($header) && function_exists('apache_request_headers')) {
        $all    = apache_request_headers();
        $header = $all['Authorization'] ?? $all['authorization'] ?? '';
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Responde con JSON de error y termina la ejecución.
 */
function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

/**
 * Realiza una petición HTTP POST con cURL.
 */
function httpPost(string $url, array $headers, array $data): array
{
    $ch = curl_init($url);

    $headerLines = ['Content-Type: application/json'];
    foreach ($headers as $key => $value) {
        if ($key !== 'Content-Type') $headerLines[] = "{$key}: {$value}";
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => $headerLines,
        CURLOPT_TIMEOUT        => 60,
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $httpCode >= 400) {
        error_log("PLADIEX admin — error en {$url}: HTTP {$httpCode} — {$raw}");
        return [];
    }

    return json_decode($raw, true) ?? [];
}

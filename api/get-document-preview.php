<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { jsonError('Método no permitido', 405); }

$token = getBearerToken();
if (!$token || !validateSupabaseJWT($token)) {
    jsonError('No autorizado', 401);
}

$body        = json_decode(file_get_contents('php://input'), true);
$safe_prefix = trim($body['safe_prefix'] ?? '');
$chunk_count = (int)($body['chunks'] ?? 0);

if (!$safe_prefix || $chunk_count < 1) {
    jsonError('Parámetros inválidos');
}

$preview_count = min(3, $chunk_count);
$ids = [];
for ($i = 0; $i < $preview_count; $i++) {
    $ids[] = $safe_prefix . '_' . $i;
}

// Pinecone fetch by vector IDs
$id_params    = implode('&', array_map(fn($id) => 'ids=' . urlencode($id), $ids));
$namespace    = urlencode(PINECONE_NAMESPACE);
$url          = PINECONE_INDEX_HOST . '/vectors/fetch?' . $id_params . '&namespace=' . $namespace;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Api-Key: ' . PINECONE_API_KEY,
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT        => 15,
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false || $code >= 400) {
    jsonError('Error al consultar Pinecone (' . $code . ')');
}

$data   = json_decode($raw, true);
$chunks = [];

foreach ($ids as $id) {
    $text = $data['vectors'][$id]['metadata']['text'] ?? null;
    if ($text !== null && $text !== '') {
        $chunks[] = $text;
    }
}

echo json_encode(['success' => true, 'chunks' => $chunks]);

// ── Helpers (misma implementación que process-pdf.php) ─────────────────────

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    if (empty($header) && function_exists('apache_request_headers')) {
        $all    = apache_request_headers();
        $header = $all['Authorization'] ?? $all['authorization'] ?? '';
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return $m[1];
    }

    return null;
}

function validateSupabaseJWT(string $token): bool
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (empty($payload)) return false;
    if (!empty($payload['exp']) && $payload['exp'] < time()) return false;

    $expectedIssuer = rtrim(SUPABASE_URL, '/') . '/auth/v1';
    if (!empty($payload['iss']) && $payload['iss'] !== $expectedIssuer) return false;

    return true;
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

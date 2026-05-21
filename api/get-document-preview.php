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

$body = json_decode(file_get_contents('php://input'), true);
$safe_prefix  = trim($body['safe_prefix'] ?? '');
$chunk_count  = (int)($body['chunks'] ?? 0);

if (!$safe_prefix || $chunk_count < 1) {
    jsonError('Parámetros inválidos');
}

// Fetch the first 3 chunk IDs from Pinecone
$preview_count = min(3, $chunk_count);
$ids = [];
for ($i = 0; $i < $preview_count; $i++) {
    $ids[] = $safe_prefix . '_' . $i;
}

$query_string = implode('&', array_map(fn($id) => 'ids=' . urlencode($id), $ids));
$query_string .= '&namespace=' . urlencode(PINECONE_NAMESPACE);

$url = PINECONE_INDEX_HOST . '/vectors/fetch?' . $query_string;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Api-Key: ' . PINECONE_API_KEY,
        'Accept: application/json',
    ],
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    jsonError('No se pudo obtener la vista previa de Pinecone');
}

$data   = json_decode($raw, true);
$chunks = [];

foreach ($ids as $id) {
    $text = $data['vectors'][$id]['metadata']['text'] ?? null;
    if ($text) {
        $chunks[] = $text;
    }
}

echo json_encode(['success' => true, 'chunks' => $chunks]);

// ── Helpers ────────────────────────────────────────────────────────────────

function getBearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? apache_request_headers()['Authorization']
           ?? '';
    return trim(str_replace('Bearer', '', $header));
}

function validateSupabaseJWT(string $token): bool
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (empty($payload)) return false;
    if (($payload['exp'] ?? 0) < time()) return false;
    if (($payload['iss'] ?? '') !== SUPABASE_URL . '/auth/v1') return false;
    return true;
}

function jsonError(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

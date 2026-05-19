<?php
/**
 * PLADIEX Admin — Eliminar documento de Pinecone
 * ================================================
 * Recibe el prefijo del documento y la cantidad de chunks,
 * reconstruye los IDs de los vectores y los elimina de Pinecone.
 *
 * Método:  DELETE
 * Headers: Authorization: Bearer <supabase_access_token>
 * Body:    { "safe_prefix": "string", "chunks": N }
 * Retorna: { "success": true, "deleted": N }
 */

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE')  { jsonError('Método no permitido', 405); }

// Autenticación
$token = getBearerToken();
if (!$token || !validateSupabaseJWT($token)) {
    jsonError('No autorizado', 401);
}

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['safe_prefix']) || !isset($body['chunks'])) {
    jsonError('Faltan campos: safe_prefix y chunks');
}

$prefix = $body['safe_prefix'];
$chunks = (int) $body['chunks'];

// Reconstruir todos los IDs de vectores del documento
$ids = [];
for ($i = 0; $i < $chunks; $i++) {
    $ids[] = $prefix . '_chunk_' . $i;
}

// Eliminar de Pinecone en lotes de 100
$batches  = array_chunk($ids, 100);
$deleted  = 0;

foreach ($batches as $batch) {
    $result = deleteFromPinecone($batch);
    if ($result) $deleted += count($batch);
}

echo json_encode(['success' => true, 'deleted' => $deleted]);


// ── Funciones ─────────────────────────────────────────────────────────────

function deleteFromPinecone(array $ids): bool
{
    $ch = curl_init(PINECONE_INDEX_HOST . '/vectors/delete');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode([
            'ids'       => $ids,
            'namespace' => PINECONE_NAMESPACE,
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Key: ' . PINECONE_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode < 400;
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    if (empty($header) && function_exists('apache_request_headers')) {
        $all    = apache_request_headers();
        $header = $all['Authorization'] ?? $all['authorization'] ?? '';
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return $m[1];
    return null;
}

function validateSupabaseJWT(string $token): bool
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (empty($payload)) return false;
    if (!empty($payload['exp']) && $payload['exp'] < time()) return false;
    return true;
}

function jsonError(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

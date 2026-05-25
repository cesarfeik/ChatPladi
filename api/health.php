<?php
/**
 * PLADIEX — Diagnóstico de conexiones
 * =====================================
 * Verifica que las APIs externas respondan correctamente.
 *
 * Método:  GET
 * Headers: Authorization: Bearer <supabase_access_token>
 * URL:     /api/health.php
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$token = getBearerToken();
if (!$token || !validateSupabaseJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$report = [];

// ─── 1. Verificar variables de entorno ─────────────────────────────────────
$report['env'] = [
    'OPENAI_API_KEY'     => !empty(OPENAI_API_KEY)     ? '✓ Configurada (' . mb_strlen(OPENAI_API_KEY) . ' chars)' : '✗ VACÍA — configura en Vercel',
    'OPENAI_CHAT_MODEL'  => OPENAI_CHAT_MODEL  ?: '✗ vacío',
    'OPENAI_EMBED_MODEL' => OPENAI_EMBED_MODEL ?: '✗ vacío',
    'PINECONE_API_KEY'   => !empty(PINECONE_API_KEY)   ? '✓ Configurada (' . mb_strlen(PINECONE_API_KEY) . ' chars)' : '✗ VACÍA — configura en Vercel',
    'PINECONE_INDEX_HOST'=> !empty(PINECONE_INDEX_HOST) ? '✓ ' . PINECONE_INDEX_HOST : '✗ VACÍA — configura en Vercel',
    'PINECONE_NAMESPACE' => PINECONE_NAMESPACE ?: '✗ vacío',
    'SUPABASE_URL'       => !empty(SUPABASE_URL)   ? '✓ Configurada' : '✗ VACÍA',
    'SUPABASE_ANON_KEY'  => !empty(SUPABASE_ANON_KEY) ? '✓ Configurada' : '✗ VACÍA',
];

// ─── 2. Test OpenAI — embedding mínimo ────────────────────────────────────
if (!empty(OPENAI_API_KEY)) {
    $t0 = microtime(true);
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model' => OPENAI_EMBED_MODEL,
            'input' => 'test',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $ms   = round((microtime(true) - $t0) * 1000);

    if ($err) {
        $report['openai'] = ['status' => 'ERROR', 'detail' => 'cURL: ' . $err];
    } elseif ($code === 200) {
        $body = json_decode($raw, true);
        $dims = count($body['data'][0]['embedding'] ?? []);
        $report['openai'] = ['status' => 'OK', 'model' => OPENAI_EMBED_MODEL, 'dims' => $dims, 'ms' => $ms];
    } elseif ($code === 401) {
        $report['openai'] = ['status' => 'ERROR', 'detail' => 'API Key inválida o expirada (401)'];
    } elseif ($code === 429) {
        $report['openai'] = ['status' => 'ERROR', 'detail' => 'Rate limit o cuota agotada (429) — revisa tu plan en platform.openai.com/usage'];
    } else {
        $body = json_decode($raw, true);
        $report['openai'] = ['status' => 'ERROR', 'http' => $code, 'detail' => $body['error']['message'] ?? $raw];
    }
} else {
    $report['openai'] = ['status' => 'SKIP', 'detail' => 'OPENAI_API_KEY no configurada'];
}

// ─── 3. Test Pinecone — describe index stats ───────────────────────────────
if (!empty(PINECONE_API_KEY) && !empty(PINECONE_INDEX_HOST)) {
    $t0 = microtime(true);
    $ch = curl_init(PINECONE_INDEX_HOST . '/describe_index_stats');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'Api-Key: ' . PINECONE_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $ms   = round((microtime(true) - $t0) * 1000);

    if ($err) {
        $report['pinecone'] = ['status' => 'ERROR', 'detail' => 'cURL: ' . $err];
    } elseif ($code === 200) {
        $body  = json_decode($raw, true);
        $total = $body['totalVectorCount'] ?? ($body['namespaces'][PINECONE_NAMESPACE]['vectorCount'] ?? '?');
        $report['pinecone'] = ['status' => 'OK', 'total_vectors' => $total, 'namespace' => PINECONE_NAMESPACE, 'ms' => $ms];
    } elseif ($code === 401 || $code === 403) {
        $report['pinecone'] = ['status' => 'ERROR', 'detail' => 'API Key de Pinecone inválida (' . $code . ')'];
    } else {
        $body = json_decode($raw, true);
        $report['pinecone'] = ['status' => 'ERROR', 'http' => $code, 'detail' => $body['message'] ?? $raw];
    }
} else {
    $report['pinecone'] = ['status' => 'SKIP', 'detail' => 'PINECONE_API_KEY o PINECONE_INDEX_HOST no configurados'];
}

// ─── 4. Resumen ─────────────────────────────────────────────────────────────
$allOk = ($report['openai']['status']   ?? '') === 'OK'
      && ($report['pinecone']['status'] ?? '') === 'OK';

$report['summary'] = $allOk
    ? '✓ Todo operativo'
    : '✗ Hay errores — revisa los campos con status ERROR arriba';

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


// ── Helpers ───────────────────────────────────────────────────────────────

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
    $expectedIssuer = rtrim(SUPABASE_URL, '/') . '/auth/v1';
    if (!empty($payload['iss']) && $payload['iss'] !== $expectedIssuer) return false;
    return true;
}

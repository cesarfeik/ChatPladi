<?php
/**
 * PLADIEX — Indexación de conversaciones en Pinecone
 * ====================================================
 * Recibe un par pregunta+respuesta y lo embebe en Pinecone para enriquecer
 * el RAG con el historial de conversaciones reales.
 *
 * Llamado por el widget de forma fire-and-forget (no bloquea el chat).
 *
 * Método:  POST
 * Body:    { "question": "string", "answer": "string", "section": "string" }
 * Retorna: { "ok": true } o { "ok": false }
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$question = trim($body['question'] ?? '');
$answer   = trim($body['answer']   ?? '');
$section  = trim($body['section']  ?? 'default');

// Descartar intercambios demasiado cortos
if (mb_strlen($question) < 3 || mb_strlen($answer) < 30) {
    echo json_encode(['ok' => false, 'error' => 'Texto demasiado corto']);
    exit;
}

$combinedText = "Pregunta: {$question}\nRespuesta: {$answer}";

// Crear embedding de la pregunta
$embedding = createEmbedding($question);
if (!$embedding) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo crear embedding']);
    exit;
}

// ID único: sección + hash + timestamp
$id = 'conv_' . preg_replace('/[^a-z0-9]/', '_', $section)
    . '_' . substr(md5($combinedText), 0, 12)
    . '_' . time();

$vector = [
    'id'       => $id,
    'values'   => $embedding,
    'metadata' => [
        'text'      => $combinedText,
        'question'  => $question,
        'answer'    => $answer,
        'section'   => $section,
        'sections'  => [$section],   // compatibilidad con filtro RAG
        'source'    => 'conversation',
        'timestamp' => date('c'),
    ],
];

$result = httpPost(
    PINECONE_INDEX_HOST . '/vectors/upsert',
    ['Api-Key' => PINECONE_API_KEY],
    ['vectors' => [$vector], 'namespace' => PINECONE_NAMESPACE]
);

echo json_encode(['ok' => !empty($result)]);


// ── Helpers ───────────────────────────────────────────────────────────────

function createEmbedding(string $text): ?array
{
    $response = httpPost(
        'https://api.openai.com/v1/embeddings',
        ['Authorization' => 'Bearer ' . OPENAI_API_KEY],
        ['model' => OPENAI_EMBED_MODEL, 'input' => $text]
    );
    return $response['data'][0]['embedding'] ?? null;
}

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
        CURLOPT_TIMEOUT        => 15,
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $httpCode >= 400) {
        error_log("PLADIEX index-conversation — error en {$url}: HTTP {$httpCode}");
        return [];
    }

    return json_decode($raw, true) ?? [];
}

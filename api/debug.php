<?php
/**
 * Endpoint de diagnóstico temporal — eliminar después de verificar
 * URL: /api/debug.php
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Probar conexión con OpenAI
$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . OPENAI_API_KEY],
    CURLOPT_TIMEOUT        => 10,
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$openaiOk = $code === 200;
$openaiMsg = $openaiOk ? 'OK' : (json_decode($raw, true)['error']['message'] ?? "HTTP $code");

echo json_encode([
    'openai_key_set'   => !empty(OPENAI_API_KEY),
    'pinecone_key_set' => !empty(PINECONE_API_KEY),
    'supabase_url_set' => !empty(SUPABASE_URL),
    'openai_status'    => $openaiMsg,
    'openai_http_code' => $code,
], JSON_PRETTY_PRINT);

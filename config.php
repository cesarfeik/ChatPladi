<?php
/**
 * PLADIEX — Configuración
 * Lee variables de entorno (Vercel) o usa valores vacíos como fallback.
 * Las keys reales se configuran en: Vercel Dashboard → Settings → Environment Variables
 */

// Lee una variable de entorno buscando en getenv(), $_ENV y $_SERVER
function env(string $key, string $default = ''): string {
    return getenv($key) ?: ($_ENV[$key] ?? ($_SERVER[$key] ?? $default));
}

// ── OpenAI ─────────────────────────────────────────────────────────────────
define('OPENAI_API_KEY',    env('OPENAI_API_KEY'));
define('OPENAI_CHAT_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'));
define('OPENAI_EMBED_MODEL',env('OPENAI_EMBED_MODEL','text-embedding-3-small'));

// ── Pinecone ────────────────────────────────────────────────────────────────
define('PINECONE_API_KEY',    env('PINECONE_API_KEY'));
define('PINECONE_INDEX_HOST', env('PINECONE_INDEX_HOST'));
define('PINECONE_NAMESPACE',  env('PINECONE_NAMESPACE', 'pladiex-docs'));

// ── Supabase ────────────────────────────────────────────────────────────────
define('SUPABASE_URL',        env('SUPABASE_URL'));
define('SUPABASE_ANON_KEY',   env('SUPABASE_ANON_KEY'));
define('SUPABASE_JWT_SECRET', env('SUPABASE_JWT_SECRET'));

// ── RAG ─────────────────────────────────────────────────────────────────────
define('RAG_TOP_K',        (int) env('RAG_TOP_K',        '5'));
define('RAG_CHUNK_SIZE',   (int) env('RAG_CHUNK_SIZE',   '1200'));
define('RAG_CHUNK_OVERLAP',(int) env('RAG_CHUNK_OVERLAP','150'));

// ── CORS ─────────────────────────────────────────────────────────────────────
define('ALLOWED_ORIGIN', env('ALLOWED_ORIGIN', '*'));

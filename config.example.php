<?php
/**
 * PLADIEX — Configuración (PLANTILLA)
 * Copia este archivo como config.php y rellena los valores reales.
 * NUNCA subas config.php al repositorio.
 */

// ── OpenAI ─────────────────────────────────────────────────────────────────
define('OPENAI_API_KEY',    'sk-...');
define('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
define('OPENAI_EMBED_MODEL','text-embedding-3-small');

// ── Pinecone ────────────────────────────────────────────────────────────────
define('PINECONE_API_KEY',     'pcsk_...');
define('PINECONE_INDEX_HOST',  'https://TU-INDICE.svc.pinecone.io');
define('PINECONE_NAMESPACE',   'pladiex-docs');

// ── Supabase ────────────────────────────────────────────────────────────────
define('SUPABASE_URL',         'https://PROYECTO.supabase.co');
define('SUPABASE_SERVICE_KEY', 'eyJ...');

// ── RAG ─────────────────────────────────────────────────────────────────────
define('RAG_TOP_K',          5);
define('RAG_CHUNK_SIZE',     300);
define('RAG_CHUNK_OVERLAP',  50);

// ── CORS ─────────────────────────────────────────────────────────────────────
define('ALLOWED_ORIGIN', 'https://pladiex.com');

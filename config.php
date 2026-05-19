<?php
/**
 * PLADIEX — Configuración
 * Lee variables de entorno (Vercel) o usa valores vacíos como fallback.
 * Las keys reales se configuran en: Vercel Dashboard → Settings → Environment Variables
 */

// ── OpenAI ─────────────────────────────────────────────────────────────────
define('OPENAI_API_KEY',    getenv('OPENAI_API_KEY')    ?: '');
define('OPENAI_CHAT_MODEL', getenv('OPENAI_CHAT_MODEL') ?: 'gpt-4o-mini');
define('OPENAI_EMBED_MODEL',getenv('OPENAI_EMBED_MODEL')?: 'text-embedding-3-small');

// ── Pinecone ────────────────────────────────────────────────────────────────
define('PINECONE_API_KEY',    getenv('PINECONE_API_KEY')    ?: '');
define('PINECONE_INDEX_HOST', getenv('PINECONE_INDEX_HOST') ?: '');
define('PINECONE_NAMESPACE',  getenv('PINECONE_NAMESPACE')  ?: 'pladiex-docs');

// ── Supabase ────────────────────────────────────────────────────────────────
define('SUPABASE_URL',        getenv('SUPABASE_URL')        ?: '');
define('SUPABASE_ANON_KEY',   getenv('SUPABASE_ANON_KEY')   ?: '');
define('SUPABASE_JWT_SECRET', getenv('SUPABASE_JWT_SECRET') ?: '');

// ── RAG ─────────────────────────────────────────────────────────────────────
define('RAG_TOP_K',        (int)(getenv('RAG_TOP_K')        ?: 5));
define('RAG_CHUNK_SIZE',   (int)(getenv('RAG_CHUNK_SIZE')   ?: 500));
define('RAG_CHUNK_OVERLAP',(int)(getenv('RAG_CHUNK_OVERLAP')?: 50));

// ── CORS ─────────────────────────────────────────────────────────────────────
define('ALLOWED_ORIGIN', getenv('ALLOWED_ORIGIN') ?: '*');

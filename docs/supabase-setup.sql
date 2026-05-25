-- ═══════════════════════════════════════════════════════════════════════════
-- PLADIEX — Supabase Setup
-- Ejecuta este SQL en: Supabase → SQL Editor
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. Crear tabla de documentos indexados en Pinecone ─────────────────────
CREATE TABLE IF NOT EXISTS documents (
  id               UUID        DEFAULT gen_random_uuid() PRIMARY KEY,
  filename         TEXT        NOT NULL,
  safe_prefix      TEXT        NOT NULL,   -- prefijo de IDs en Pinecone
  chunks           INTEGER     NOT NULL,
  uploaded_by      TEXT,                   -- email del admin que subió el doc
  content_preview  TEXT,                   -- primeros 4,000 chars del texto
  sections         TEXT,                   -- JSON array: ["tienda","home"] o ["all"]
  uploaded_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ── 2. Migración — agregar columnas si la tabla ya existe ──────────────────
-- (Seguro de ejecutar en tablas existentes; ignora columnas ya presentes)
ALTER TABLE documents
  ADD COLUMN IF NOT EXISTS uploaded_by     TEXT,
  ADD COLUMN IF NOT EXISTS content_preview TEXT,
  ADD COLUMN IF NOT EXISTS sections        TEXT;

-- ── 3. RLS: solo usuarios autenticados pueden operar ──────────────────────
ALTER TABLE documents ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Admins pueden leer documentos"
  ON documents FOR SELECT
  USING (auth.role() = 'authenticated');

CREATE POLICY "Admins pueden insertar documentos"
  ON documents FOR INSERT
  WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "Admins pueden eliminar documentos"
  ON documents FOR DELETE
  USING (auth.role() = 'authenticated');

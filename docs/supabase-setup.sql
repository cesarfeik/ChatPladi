-- Ejecuta este SQL en Supabase → SQL Editor
-- Crea la tabla que registra los documentos indexados en Pinecone

CREATE TABLE IF NOT EXISTS documents (
  id            UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  filename      TEXT NOT NULL,
  safe_prefix   TEXT NOT NULL,  -- prefijo usado para los IDs en Pinecone
  chunks        INTEGER NOT NULL,
  uploaded_at   TIMESTAMPTZ DEFAULT NOW()
);

-- Política: solo usuarios autenticados pueden leer, insertar y eliminar
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

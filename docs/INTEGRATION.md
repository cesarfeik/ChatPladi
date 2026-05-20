# PLADIEX Chatbot — Documentación de Integración

> Última actualización: Mayo 2026  
> Repositorio: https://github.com/cesarfeik/ChatPladi  
> Demo en producción: https://chat-pladi.vercel.app

---

## Índice

1. [¿Qué construimos?](#1-qué-construimos)
2. [Arquitectura del sistema](#2-arquitectura-del-sistema)
3. [Estructura de archivos](#3-estructura-de-archivos)
4. [Servicios externos](#4-servicios-externos)
5. [Configuración — Variables de entorno](#5-configuración--variables-de-entorno)
6. [Integrar el chatbot en el sitio](#6-integrar-el-chatbot-en-el-sitio)
7. [Panel de administración](#7-panel-de-administración)
8. [Cómo funciona el RAG](#8-cómo-funciona-el-rag)
9. [Flujo completo de una conversación](#9-flujo-completo-de-una-conversación)
10. [Despliegue — Vercel (actual)](#10-despliegue--vercel-actual)
11. [Migración a Hostinger (futuro)](#11-migración-a-hostinger-futuro)
12. [Roadmap de chatbots](#12-roadmap-de-chatbots)
13. [Preguntas frecuentes](#13-preguntas-frecuentes)

---

## 1. ¿Qué construimos?

Un **asistente virtual inteligente** llamado **Alex Ciencia** para el sitio de PLADIEX que puede:

- Responder preguntas sobre la plataforma usando documentos reales (PDFs indexados).
- Orientar a usuarios que describen síntomas hacia el especialista adecuado.
- Mostrar **tarjetas de acceso directo** a secciones del sitio (Tienda, Citas, Perfil, etc.) con ícono, título y descripción.
- Mostrar **chips de acceso rápido** al inicio para guiar al usuario.

Se añade al sitio con **una sola línea de código** y no toca nada del sitio existente.

---

## 2. Arquitectura del sistema

```
┌─────────────────────────────────────────────┐
│           SITIO pladiex.com                 │
│  <script src="https://chat-pladi.vercel.app/chatbot/widget.js"
│           data-api="https://chat-pladi.vercel.app/api/chat.php">
└──────────────────┬──────────────────────────┘
                   │ Usuario escribe un mensaje
                   ▼
      ┌────────────────────────┐
      │  chatbot/widget.js     │  Widget visual (botón + panel de chat)
      │  chatbot/widget.css    │  Estilos con colores de PLADIEX
      └────────────┬───────────┘
                   │ POST /api/chat.php
                   ▼
      ┌────────────────────────┐
      │     api/chat.php       │  Backend PHP — orquesta el RAG
      └──────┬─────────────────┘
             │                │
             ▼                ▼
      ┌──────────┐    ┌────────────────┐
      │  OpenAI  │    │   Pinecone     │
      │ embed +  │    │  Vector DB     │
      │ GPT-4o-  │    │  (documentos   │
      │  mini    │    │   indexados)   │
      └──────────┘    └────────────────┘

─────────────────────────────────────────────
       PANEL ADMIN
─────────────────────────────────────────────

  chat-pladi.vercel.app/admin/      → Login (Supabase Auth)
  chat-pladi.vercel.app/admin/dashboard.html → Subir PDFs + biblioteca

        │ POST /api/process-pdf.php
        ▼
  PDF.js extrae texto en el navegador
  → PHP crea embeddings (OpenAI)
  → Guarda vectores en Pinecone
  → Registra documento en Supabase (tabla documents)
```

---

## 3. Estructura de archivos

```
ChatPladi/                          ← Raíz del repositorio
│
├── config.php                      ← Lee variables de entorno (Vercel) o $_ENV
├── config.example.php              ← Plantilla sin valores reales
├── vercel.json                     ← Configuración del runtime PHP en Vercel
├── index.html                      ← Página demo del chatbot
├── .env                            ← Keys reales (gitignored, solo local/Vercel)
│
├── api/                            ← Funciones serverless PHP (Vercel)
│   ├── chat.php                    ← RAG: embedding → Pinecone → GPT → respuesta
│   ├── process-pdf.php             ← Indexar PDF en Pinecone (requiere JWT admin)
│   └── delete-document.php        ← Eliminar vectores de Pinecone (requiere JWT admin)
│
├── chatbot/
│   ├── widget.js                   ← Widget embebible completo
│   └── widget.css                  ← Estilos del chat
│
├── admin/
│   ├── index.html                  ← Login con Supabase
│   ├── dashboard.html              ← Subir PDFs + biblioteca de documentos
│   └── css/
│       └── admin.css               ← Estilos del panel admin
│
└── docs/
    ├── INTEGRATION.md              ← Esta documentación
    └── supabase-setup.sql          ← SQL para crear tabla documents en Supabase
```

---

## 4. Servicios externos

| Servicio | Para qué | Plan actual |
|---|---|---|
| **OpenAI** | Embeddings (`text-embedding-3-small`) + chat (`gpt-4o-mini`) | De pago por uso |
| **Pinecone** | Base de datos vectorial, namespace `pladiex-docs` | Gratuito (100k vectores) |
| **Supabase** | Auth del admin + tabla `documents` (biblioteca de PDFs) | Gratuito |
| **Vercel** | Hosting del chatbot + funciones PHP serverless | Gratuito |
| **GitHub** | Repositorio: `cesarfeik/ChatPladi` | Gratuito |

### Costos estimados
- **OpenAI embeddings**: ~$0.02 / millón de tokens — prácticamente gratis.
- **OpenAI chat**: ~$0.15 / millón de tokens entrada — muy económico con gpt-4o-mini.
- **Pinecone**: gratuito hasta 100,000 vectores (~2,000 páginas de PDF).

---

## 5. Configuración — Variables de entorno

`config.php` lee las keys desde variables de entorno. **Nunca escribas las keys directamente en el código.**

### En Vercel (producción)
Ve a **Vercel → tu proyecto → Settings → Environment Variables** e importa:

```env
OPENAI_API_KEY=sk-proj-...
OPENAI_CHAT_MODEL=gpt-4o-mini
OPENAI_EMBED_MODEL=text-embedding-3-small

PINECONE_API_KEY=pcsk_...
PINECONE_INDEX_HOST=https://tu-indice.svc.pinecone.io
PINECONE_NAMESPACE=pladiex-docs

SUPABASE_URL=https://xxxx.supabase.co
SUPABASE_ANON_KEY=eyJ...
SUPABASE_JWT_SECRET=...

RAG_TOP_K=5
RAG_CHUNK_SIZE=500
RAG_CHUNK_OVERLAP=50

ALLOWED_ORIGIN=*
```

Después de agregar o cambiar variables, hacer **Redeploy** para que tomen efecto.

### En XAMPP (desarrollo local)
Edita `C:\xampp\htdocs\pladiex\config.php` con los valores reales hardcodeados (este archivo NO está en el repositorio).

### En Hostinger (producción futura)
Sube `config.php` con los valores reales via FTP. El `.htaccess` ya incluye la regla para que el header `Authorization` llegue a PHP.

---

## 6. Integrar el chatbot en el sitio

Añade esta línea antes del `</body>` en cualquier página de pladiex.com:

```html
<!-- Chatbot general PLADIEX -->
<script
  src="https://chat-pladi.vercel.app/chatbot/widget.js"
  data-api="https://chat-pladi.vercel.app/api/chat.php">
</script>
```

Para mostrar el logo de PLADIEX en el header del chat:
```html
<script
  src="https://chat-pladi.vercel.app/chatbot/widget.js"
  data-api="https://chat-pladi.vercel.app/api/chat.php"
  data-logo="https://pladiex.com/ruta/al/logo.webp">
</script>
```

El widget aparece automáticamente como botón flotante en la esquina inferior derecha. No modifica el CSS ni el JS existente del sitio.

### Chips de acceso rápido (configurables en widget.js)

```javascript
const QUICK_OPTIONS = [
  { label: 'PRE-DIAGNÓSTICO', value: 'Quiero hacer un pre-diagnóstico...', color: '#E05C8A' },
  { label: 'TIENDA',          value: 'Quiero ir a la tienda de PLADIEX', color: '#E7BA11' },
  // ...
];
```

### Botones de navegación (configurables en widget.js y api/chat.php)

Cuando el usuario menciona palabras clave (tienda, cita, préstamo...) aparecen tarjetas con:
- Ícono de categoría
- Título y descripción de a dónde lleva
- Flecha animada al hover

```javascript
const NAV_MAP = [
  { keywords: ['tienda', 'comprar'],
    label: 'Ir a la Tienda', sub: 'Ver productos y ofertas',
    icon: '🛍️', url: 'https://pladiex.com/mall/', color: '#E7BA11' },
  // ...
];
```

---

## 7. Panel de administración

**URL:** `https://chat-pladi.vercel.app/admin/`

### Acceso
Solo usuarios creados en Supabase → Authentication → Users (actualmente 3 cuentas admin).

### Subir documentos
1. Inicia sesión con tu cuenta admin.
2. Arrastra PDFs al área de carga (máx. 10 MB cada uno).
3. Clic en **"Procesar e indexar en Pinecone"**.
4. El sistema extrae texto con PDF.js, crea embeddings con OpenAI y guarda en Pinecone.
5. Los archivos procesados exitosamente desaparecen de la cola automáticamente (~1.5s después).
6. Si hay error, el archivo permanece en la cola para reintentar.

### Biblioteca de documentos
- Muestra todos los PDFs indexados (guardados en tabla `documents` de Supabase).
- Cada documento muestra nombre, número de fragmentos y fecha de subida.
- Botón **Eliminar** borra los vectores de Pinecone y el registro de Supabase.

### ¿Qué documentos subir?
- Información de servicios y planes de PLADIEX
- Guías para pacientes
- Preguntas frecuentes
- Política de precios, membresías
- Información de especialidades médicas disponibles

---

## 8. Cómo funciona el RAG

**RAG** (Retrieval-Augmented Generation) permite que el bot responda usando información real de tus documentos.

### Al subir un PDF:
```
PDF
 └─► PDF.js extrae el texto (en el navegador, sin subir el archivo)
      └─► Se divide en fragmentos de ~500 palabras (overlap 50)
           └─► Cada fragmento → embedding de 1536 dimensiones (OpenAI)
                └─► Vectores guardados en Pinecone con metadata:
                    { text, filename, chunk, source: "pdf_upload" }
```

### Al recibir una pregunta:
```
Pregunta del usuario
 └─► Se convierte en vector (OpenAI text-embedding-3-small)
      └─► Búsqueda semántica en Pinecone → top 5 fragmentos más relevantes
           └─► Prompt: system + contexto RAG + historial (10 turnos) + pregunta
                └─► GPT-4o-mini genera respuesta (temp 0.4, max 600 tokens)
                     └─► Respuesta + botones de navegación detectados
```

---

## 9. Flujo completo de una conversación

```
Usuario abre el sitio
  │
  ▼
Widget aparece (botón flotante azul-teal, badge amarillo)
  │
Usuario hace clic → panel se abre con animación
  │
  ├─► Mensaje de bienvenida de Alex Ciencia
  └─► Chips de colores: PRE-DIAGNÓSTICO / TIENDA / CITAS / PRÉSTAMOS / FAQ
  │
Usuario elige chip o escribe mensaje
  │
  ▼
POST /api/chat.php  { message, history }
  │
  ├─► Embedding del mensaje (OpenAI)
  ├─► Query Pinecone → contexto relevante
  ├─► Detección de intención de navegación
  └─► GPT-4o-mini con system prompt + contexto + historial
  │
  ▼
Respuesta renderizada en el chat:
  ├─► Burbuja con borde azul, animación de entrada
  └─► Tarjetas de navegación si aplica (ícono + título + descripción + flecha)
```

---

## 10. Despliegue — Vercel (actual)

El proyecto está desplegado en Vercel conectado al repositorio `cesarfeik/ChatPladi`.

**Cada `git push` a `main` redespliega automáticamente.**

```bash
# Desde C:\Users\cesar\Documents\Pladiex bot
git add .
git commit -m "descripción del cambio"
git push origin main
```

### URLs activas
| Recurso | URL |
|---|---|
| Demo chatbot | https://chat-pladi.vercel.app |
| Panel admin (login) | https://chat-pladi.vercel.app/admin/ |
| Panel admin (dashboard) | https://chat-pladi.vercel.app/admin/dashboard.html |
| API chat | https://chat-pladi.vercel.app/api/chat.php |
| API process PDF | https://chat-pladi.vercel.app/api/process-pdf.php |

### Runtime PHP en Vercel
`vercel.json` configura el runtime `vercel-php@0.7.2` para los archivos en `api/*.php`.

---

## 11. Migración a Hostinger (futuro)

Cuando se migre a Hostinger (hosting principal de pladiex.com):

1. Subir todos los archivos via FTP o Administrador de archivos.
2. Crear `config.php` en el servidor con las keys reales (mismo formato que XAMPP).
3. El `.htaccess` en `api/` ya está incluido para pasar el header `Authorization`.
4. Cambiar `ALLOWED_ORIGIN` a `'https://pladiex.com'`.
5. Actualizar el script tag en pladiex.com con las rutas del nuevo servidor.

No requiere cambios de código — el PHP es compatible con Apache/Hostinger.

---

## 12. Roadmap de chatbots

El mismo stack soporta múltiples chatbots especializados, cada uno con su propio namespace en Pinecone:

| Chatbot | Namespace Pinecone | Estado | Notas |
|---|---|---|---|
| **General PLADIEX** | `pladiex-docs` | ✅ En producción | Chatbot actual |
| **Tienda** | `pladiex-tienda` | 📋 Planeado | Conectado a BD de productos, function calling |
| **E-learning** | `elearning-{curso}` | 📋 Planeado | Por curso, admin con selector de curso |
| **Expediente médico** | `paciente-{id}` | 📋 Planeado | Paciente sube docs, doctor consulta |

La integración en cada sección solo requiere cambiar el `data-api`:
```html
<!-- En la tienda -->
<script src=".../widget.js" data-api=".../api/chat-tienda.php"></script>

<!-- En e-learning, pasando el ID del curso -->
<script src=".../widget.js" data-api=".../api/chat-elearning.php?curso=cardiologia-basica"></script>
```

---

## 13. Preguntas frecuentes

**¿El bot responde sin documentos subidos?**
Sí, pero con conocimiento general. Para respuestas precisas sobre PLADIEX, sube PDFs desde el admin.

**¿Cuántos PDFs puedo subir?**
Pinecone gratuito soporta 100,000 vectores (~2,000 páginas de PDF).

**¿El bot recuerda conversaciones anteriores?**
No. El historial dura solo la sesión actual del navegador.

**¿Cómo actualizo la información del bot?**
Sube nuevos PDFs desde el panel admin. Los cambios son inmediatos.

**¿Puedo cambiar el modelo de IA?**
Sí. Cambia `OPENAI_CHAT_MODEL` en las variables de entorno de Vercel (ej: `gpt-4o` para más capacidad).

**¿El chatbot da diagnósticos médicos?**
No. Da orientación general y siempre recomienda consultar a un médico real.

**¿Cómo conecto el chatbot a pladiex.com?**
Una línea antes del `</body>`. Ver sección 6.

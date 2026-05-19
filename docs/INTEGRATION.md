# PLADIEX Chatbot — Documentación de Integración

> Este documento explica qué hace cada pieza del sistema, cómo configurarlo y cómo integrarlo al sitio pladiex.com paso a paso.

---

## Índice

1. [¿Qué construimos?](#1-qué-construimos)
2. [Arquitectura del sistema](#2-arquitectura-del-sistema)
3. [Estructura de archivos](#3-estructura-de-archivos)
4. [Servicios externos que se usan](#4-servicios-externos-que-se-usan)
5. [Configuración inicial](#5-configuración-inicial)
6. [Integrar el chatbot en el sitio](#6-integrar-el-chatbot-en-el-sitio)
7. [Usar el panel de administración](#7-usar-el-panel-de-administración)
8. [Cómo funciona el RAG (Inteligencia del bot)](#8-cómo-funciona-el-rag)
9. [Flujo completo de una conversación](#9-flujo-completo-de-una-conversación)
10. [Pruebas locales](#10-pruebas-locales)
11. [Despliegue en producción](#11-despliegue-en-producción)
12. [Preguntas frecuentes](#12-preguntas-frecuentes)

---

## 1. ¿Qué construimos?

Un **asistente virtual inteligente** para el sitio de PLADIEX que puede:

- Responder preguntas sobre la plataforma usando documentos reales (PDFs).
- Orientar a usuarios que describen síntomas hacia el especialista adecuado.
- Mostrar **accesos directos** a secciones del sitio (Tienda, Perfil, Médicos, etc.).
- Mostrar **opciones rápidas** al inicio para que el usuario sepa qué puede preguntar.

El chatbot se añade al sitio con **una sola línea de código** (script tag) y no requiere modificar el código PHP existente.

---

## 2. Arquitectura del sistema

```
┌────────────────────────────────────────────┐
│              SITIO pladiex.com             │
│   (HTML + PHP + JS existente)              │
│                                            │
│   <script src="/chatbot/widget.js">  ◄──── │─── Un solo tag en el HTML
└────────────┬───────────────────────────────┘
             │  El usuario escribe un mensaje
             ▼
┌────────────────────────┐
│  chatbot/widget.js     │  Widget visual (botón + panel de chat)
│  chatbot/widget.css    │  Estilos con colores de PLADIEX
└────────────┬───────────┘
             │  POST /chatbot/api/chat.php
             ▼
┌────────────────────────┐
│  chatbot/api/chat.php  │  Backend PHP — orquesta todo el RAG
└──┬─────────────────────┘
   │                     │
   ▼                     ▼
┌──────────┐      ┌──────────────┐
│  OpenAI  │      │   Pinecone   │
│ Embeddings│      │ Vector DB    │
│ GPT-4o-  │      │ (documentos  │
│  mini    │      │  indexados)  │
└──────────┘      └──────────────┘

─────────────────────────────────────────

┌────────────────────────────────────────┐
│  PANEL ADMIN (dominio/subdominio sep.) │
│  admin/index.html    → Login           │
│  admin/dashboard.html → Subir PDFs     │
└────────────┬───────────────────────────┘
             │  POST /admin/api/process-pdf.php
             ▼
        Extrae texto (PDF.js en navegador)
        → crea embeddings (OpenAI)
        → guarda vectores (Pinecone)
```

---

## 3. Estructura de archivos

```
Pladiex bot/
│
├── config.php                    ← Claves de API (NUNCA subir con datos reales)
│
├── chatbot/
│   ├── widget.js                 ← Widget embebible (se carga con <script>)
│   ├── widget.css                ← Estilos del chat (colores PLADIEX)
│   └── api/
│       └── chat.php              ← Backend: recibe mensaje → RAG → respuesta
│
├── admin/
│   ├── index.html                ← Página de login (Supabase Auth)
│   ├── dashboard.html            ← Panel para subir PDFs
│   ├── css/
│   │   └── admin.css             ← Estilos del panel admin
│   └── api/
│       └── process-pdf.php       ← Procesa PDFs y los indexa en Pinecone
│
└── docs/
    └── INTEGRATION.md            ← Esta documentación
```

---

## 4. Servicios externos que se usan

| Servicio | Para qué | Dónde registrarse |
|---|---|---|
| **OpenAI** | Crear embeddings + generar respuestas (GPT-4o-mini) | platform.openai.com |
| **Pinecone** | Guardar y buscar vectores de documentos | app.pinecone.io |
| **Supabase** | Autenticación del panel admin | app.supabase.com |

### Costo aproximado (referencia)
- **OpenAI embeddings** (`text-embedding-3-small`): ~$0.02 por millón de tokens — muy barato.
- **OpenAI chat** (`gpt-4o-mini`): ~$0.15 / millón de tokens de entrada — económico.
- **Pinecone** plan gratuito: 1 índice, 100,000 vectores — suficiente para empezar.
- **Supabase** plan gratuito: 50,000 usuarios activos al mes — más que suficiente para 3 admins.

---

## 5. Configuración inicial

### Paso 1 — Editar `config.php`

Abre `config.php` y reemplaza los valores de ejemplo con tus claves reales:

```php
define('OPENAI_API_KEY',    'sk-tu-clave-real');
define('PINECONE_API_KEY',  'tu-clave-pinecone');
define('PINECONE_INDEX_HOST', 'https://tu-indice.svc.us-east1-gcp.pinecone.io');
define('SUPABASE_URL',      'https://tu-proyecto.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJ...');
```

### Paso 2 — Crear índice en Pinecone

1. Entra a [app.pinecone.io](https://app.pinecone.io)
2. Crea un nuevo índice con estos parámetros:
   - **Name:** `pladiex-docs` (o el que prefieras)
   - **Dimensions:** `1536` (dimensiones de `text-embedding-3-small`)
   - **Metric:** `cosine`
3. Copia el **Host URL** y pégalo en `PINECONE_INDEX_HOST` en `config.php`

### Paso 3 — Crear usuarios admin en Supabase

1. Entra a [app.supabase.com](https://app.supabase.com)
2. Ve a **Authentication → Users → Invite user**
3. Crea los 3 correos admin
4. Copia la **URL del proyecto** y la **anon key** desde Settings → API

### Paso 4 — Poner las claves en el admin HTML

En `admin/index.html` y `admin/dashboard.html`, reemplaza:
```javascript
const SUPABASE_URL      = 'https://XXXXXXXXXXXX.supabase.co';
const SUPABASE_ANON_KEY = 'eyJ...';
```
con los valores reales de tu proyecto Supabase.

---

## 6. Integrar el chatbot en el sitio

Añade esta **única línea** antes del `</body>` en las páginas PHP donde quieras el chatbot:

```html
<script src="/chatbot/widget.js" data-api="/chatbot/api/chat.php"></script>
```

**Para el landing page** (inicio), agrégalo en el archivo principal (normalmente `index.php` o `index.html`):

```html
  <!-- ... resto del contenido ... -->

  <!-- PLADIEX Chatbot -->
  <script src="/chatbot/widget.js" data-api="/chatbot/api/chat.php"></script>
</body>
</html>
```

El widget se carga automáticamente — aparece un botón flotante en la esquina inferior derecha.

### Personalizar opciones rápidas

Las opciones que aparecen al inicio de la conversación se definen en `widget.js`, en la sección `QUICK_OPTIONS`. Puedes añadir, quitar o cambiar las opciones editando ese arreglo:

```javascript
const QUICK_OPTIONS = [
  { label: '🤒 Tengo síntomas',     value: 'Tengo algunos síntomas y quiero orientación' },
  { label: '❓ ¿Qué es PLADIEX?',   value: '¿Qué es PLADIEX y qué servicios ofrece?' },
  // ... más opciones
];
```

### Personalizar accesos directos (links)

Los links que aparecen cuando el usuario menciona "tienda", "perfil", etc. se definen en `chatbot/api/chat.php`, en la función `detectNavigationLinks()`. Puedes actualizar las URLs ahí cuando el sitio cambie.

---

## 7. Usar el panel de administración

### Acceso

El panel admin vive en un **dominio o ruta separada**. Para pruebas locales accede a:
```
http://localhost/admin/index.html
```

### Subir documentos

1. Inicia sesión con una cuenta admin (creada en Supabase).
2. En la sección **Subir PDF**, arrastra uno o varios PDFs al área de drop (o haz clic para seleccionarlos).
3. Haz clic en **"Procesar e indexar en Pinecone"**.
4. El sistema extrae el texto, lo divide en fragmentos y crea un embedding por cada uno.
5. Los vectores se guardan en Pinecone bajo el namespace `pladiex-docs`.
6. El log en tiempo real te muestra el progreso.

### ¿Qué documentos subir?

Sube cualquier documento que quieras que el chatbot conozca:
- Información de servicios de PLADIEX
- Guías para pacientes
- Listado de especialidades médicas
- Preguntas frecuentes
- Información de precios o planes

---

## 8. Cómo funciona el RAG

**RAG** (Retrieval-Augmented Generation) es la técnica que hace que el bot responda con información real de tus documentos en lugar de "inventar" respuestas.

### Al subir un PDF:

```
PDF
 └─► PDF.js extrae el texto
      └─► Se divide en fragmentos de ~500 palabras con solapamiento de 50
           └─► Cada fragmento se convierte en un vector (embedding) con OpenAI
                └─► Los vectores se guardan en Pinecone con el texto como metadata
```

### Al recibir una pregunta:

```
Pregunta del usuario
 └─► Se convierte en vector (embedding) con OpenAI
      └─► Se buscan los 5 fragmentos más similares en Pinecone (búsqueda semántica)
           └─► Se construye un prompt: sistema + contexto encontrado + historial + pregunta
                └─► GPT-4o-mini genera la respuesta
                     └─► Se envía al widget con botones de navegación si aplica
```

### ¿Por qué es mejor que solo usar GPT?

Porque GPT no conoce la información específica de PLADIEX. Con RAG:
- El bot usa **tus documentos reales** para responder.
- Las respuestas son precisas y actualizables (solo sube nuevos PDFs).
- No "alucina" información que no existe en tus documentos.

---

## 9. Flujo completo de una conversación

```
Usuario abre el sitio
  │
  ▼
Widget aparece (botón flotante azul)
  │
  ├─► Notificación amarilla en el botón (badge)
  │
Usuario hace clic
  │
  ▼
Panel se abre con:
  ├─► Mensaje de bienvenida del bot
  └─► Opciones rápidas (chips): Síntomas / ¿Qué es PLADIEX? / etc.
  │
Usuario elige una opción o escribe
  │
  ▼
widget.js envía POST a chat.php con:
  ├─► message: texto del usuario
  └─► history: conversación previa (últimos 10 turnos)
  │
  ▼
chat.php:
  1. Crea embedding del mensaje (OpenAI)
  2. Busca los 5 fragmentos más relevantes en Pinecone
  3. Detecta si hay intención de navegar (tienda, perfil, etc.)
  4. Construye prompt con contexto y llama a GPT-4o-mini
  5. Devuelve: { reply: "...", links: [...] }
  │
  ▼
widget.js muestra:
  ├─► Respuesta del bot (con formato)
  └─► Botones de acceso directo si aplica (ej: "🛒 Ir a la Tienda")
```

---

## 10. Pruebas locales

### Requisitos

- **PHP 7.4+** con extensión `curl` habilitada
- Servidor web local (XAMPP, WAMP, Laragon, o el servidor built-in de PHP)

### Con PHP built-in (sin instalar XAMPP)

```bash
# Desde la carpeta raíz del proyecto
php -S localhost:8000
```

Luego abre:
- Chatbot demo: `http://localhost:8000/test.html` (crear un HTML de prueba)
- Panel admin: `http://localhost:8000/admin/index.html`

### Crear HTML de prueba para el chatbot

Crea un archivo `test.html` en la raíz:

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba Chatbot PLADIEX</title>
</head>
<body>
  <h1>Prueba del chatbot</h1>
  <p>El botón del chat debe aparecer en la esquina inferior derecha.</p>

  <script src="/chatbot/widget.js" data-api="/chatbot/api/chat.php"></script>
</body>
</html>
```

### Verificar que PHP puede conectarse a internet

```bash
php -r "echo file_get_contents('https://api.openai.com'); echo 'OK';"
```

Si no funciona, verifica que `allow_url_fopen = On` y `curl` está habilitado en tu `php.ini`.

---

## 11. Despliegue en producción

### En el servidor de pladiex.com

1. **Sube todos los archivos** via FTP/SFTP o Git al servidor.
2. **Protege `config.php`** añadiendo en `.htaccess`:
   ```apache
   <Files "config.php">
     Order allow,deny
     Deny from all
   </Files>
   ```
3. **Cambia `ALLOWED_ORIGIN`** en `config.php`:
   ```php
   define('ALLOWED_ORIGIN', 'https://pladiex.com');
   ```
4. **Añade el script tag** al layout principal del sitio PHP.
5. **Sube los PDFs** desde el panel admin.
6. **Prueba** enviando un mensaje al chatbot y verificando que responde con información de los PDFs.

### Panel admin en subdominio separado

Si el admin estará en `admin.pladiex.com`:
1. Configura el subdominio en tu hosting para apuntar a la carpeta `admin/`.
2. Actualiza la variable `PROCESS_API` en `dashboard.html` con la URL completa:
   ```javascript
   const PROCESS_API = 'https://admin.pladiex.com/api/process-pdf.php';
   ```
3. Actualiza `ALLOWED_ORIGIN` en `config.php` para permitir el subdominio.

---

## 12. Preguntas frecuentes

**¿El bot puede responder sin documentos subidos?**
Sí, pero solo con conocimiento general. Para que use información de PLADIEX, debes subir PDFs desde el panel admin primero.

**¿Cuántos PDFs puedo subir?**
Tantos como quieras. Pinecone en plan gratuito soporta 100,000 vectores. Un PDF de 10 páginas genera aproximadamente 20-40 vectores.

**¿El bot recuerda conversaciones anteriores entre sesiones?**
No. El historial se mantiene solo durante la sesión actual del navegador. Al recargar la página, la conversación empieza de nuevo.

**¿Cómo actualizo la información del bot?**
Solo sube nuevos PDFs desde el panel admin. Los cambios se reflejan de inmediato en el chatbot.

**¿Puedo cambiar el modelo de IA?**
Sí. En `config.php` cambia `OPENAI_CHAT_MODEL` a cualquier modelo de OpenAI (ej: `gpt-4o` para más capacidad, `gpt-3.5-turbo` para más velocidad/economía).

**¿El chatbot da diagnósticos médicos?**
No. Está programado para dar orientación general y siempre recomendar consultar a un médico. No reemplaza una consulta médica real.

**¿Dónde veo los errores si algo falla?**
- Errores PHP: en el log de errores del servidor (o activa `display_errors` temporalmente en desarrollo).
- Errores de API: revisa la consola del navegador (F12) y el log del servidor.
- El endpoint `chat.php` devuelve mensajes de error en el campo `error` del JSON.

# Guía de Pruebas — PLADIEX Chatbot & Admin Panel

## Paso 0 — Preparar los PDFs

Abre cada archivo HTML en tu navegador y presiona `Ctrl+P → Guardar como PDF`:

| Archivo HTML | PDF resultante | Sección a asignar |
|---|---|---|
| `financiamiento-guia.html` | `financiamiento-guia.pdf` | ✅ `financiamiento` |
| `tienda-catalogo.html` | `tienda-catalogo.pdf` | ✅ `tienda` |
| `servicios-pladiex.html` | `servicios-pladiex.pdf` | ✅ `servicios` |
| `faq-general.html` | `faq-general.pdf` | ✅ `all` (aplica a todas) |

---

## Prueba 1 — Chat básico funciona

**Dónde:** https://chat-pladi.vercel.app/test.html  
**Sección:** cualquiera (ej. `default`)

**Pasos:**
1. Abre el test.html
2. Escribe: `Hola, ¿qué es PLADIEX?`
3. Presiona Enter

**Resultado esperado:**
- ✅ Aparece "Alex Ciencia está escribiendo..." con animación
- ✅ Responde en menos de 8 segundos
- ✅ La respuesta es coherente sobre PLADIEX
- ❌ NO debe aparecer "Hubo un problema de conexión"

---

## Prueba 2 — Clasificación por archivo (bug corregido)

**Dónde:** https://chat-pladi.vercel.app/admin/  

**Pasos:**
1. Arrastra los 4 PDFs al área de carga al mismo tiempo
2. Verifica que cada archivo tiene su propia fila de botones de sección
3. Al `financiamiento-guia.pdf` → haz clic en **Financiamiento** (debe quedar activo/azul)
4. Al `tienda-catalogo.pdf` → haz clic en **Tienda**
5. Al `servicios-pladiex.pdf` → haz clic en **Servicios**
6. Al `faq-general.pdf` → debe quedar en **Todas** (default)
7. Haz clic en **Procesar e indexar**

**Resultado esperado:**
- ✅ Cada archivo mantiene su sección independiente (no se sobreescriben entre sí)
- ✅ El botón de la sección seleccionada se queda activo (azul/resaltado)
- ✅ Si agregas otro archivo después, los anteriores no cambian
- ✅ Los 4 archivos se procesan y aparecen en la Biblioteca

---

## Prueba 3 — Filtrado RAG por sección

**Prerequisito:** Haber subido los PDFs de la Prueba 2.

### 3a. Pregunta en sección `financiamiento`

**Dónde:** https://chat-pladi.vercel.app/test.html → pestaña **Financiamiento**

**Pregunta:** `¿Cuáles son los plazos y tasas de interés para el segmento B?`

**Resultado esperado:**
- ✅ Menciona: 30% tasa anual, plazos 6/12/18/24 meses, montos $50,001–$150,000 MXN
- ✅ NO menciona información de la tienda o servicios genéricos

---

### 3b. Pregunta en sección `tienda`

**Dónde:** pestaña **Tienda**

**Pregunta:** `¿Cuánto cuesta el monitor de signos vitales de Mindray?`

**Resultado esperado:**
- ✅ Responde: ~$42,000 MXN, financiable con crédito PLADIEX
- ✅ Ofrece información de envíos o cómo comprar

---

### 3c. Verificar que el FAQ aplica a todas las secciones

**Dónde:** pestaña **Contacto** (o cualquiera)

**Pregunta:** `¿Cuánto tiempo tarda la aprobación del crédito?`

**Resultado esperado:**
- ✅ Responde: menos de 48 horas hábiles
- ✅ Información tomada del FAQ (documento `all`)

---

## Prueba 4 — Accesos rápidos por sección

**Dónde:** https://chat-pladi.vercel.app/test.html

| Pestaña | Chips esperados |
|---------|----------------|
| Default | PRE-DIAGNÓSTICO, TIENDA, CITAS, PRÉSTAMOS, PREGUNTAS FRECUENTES |
| Home | PRE-DIAGNÓSTICO, FINANCIAMIENTO, TIENDA, CITAS, ¿QUÉ ES PLADIEX? |
| Financiamiento | SIMULAR PAGO, SOLICITAR CRÉDITO, REQUISITOS, PLAZOS Y TASAS, ELEGIR MÉDICO |
| Servicios | SOY PACIENTE, SOY MÉDICO, ASOCIACIÓN, UNIVERSIDAD, REGISTRARME |
| Tienda | EQUIPOS DX, INSTRUMENTAL, TECNOLOGÍA, ROPA MÉDICA, FINANCIAR COMPRA |
| Contacto | WHATSAPP, HORARIOS, AGENDAR REUNIÓN, EMAIL, PREGUNTAS FREQ. |

**Pasos:**
1. Cambia de pestaña y abre el chat
2. Verifica que los chips son los correctos para cada sección
3. Haz clic en un chip y confirma que envía el mensaje correspondiente

---

## Prueba 5 — Indexación de conversaciones (fire-and-forget)

**Dónde:** Consola del navegador (F12 → Network)

**Pasos:**
1. Abre test.html
2. Abre DevTools (F12) → pestaña **Network**
3. Filtra por: `index-conversation`
4. Escribe cualquier mensaje en el chat y envía
5. Espera la respuesta del bot

**Resultado esperado:**
- ✅ Aparece una petición POST a `/api/index-conversation.php` en el Network
- ✅ La petición llega con status `200` (puede verse con un pequeño retraso)
- ✅ El chat respondió ANTES de que se completara esa petición (fire-and-forget)
- ✅ En el panel admin → Diagnóstico → `total_vectors` aumenta con el tiempo

---

## Prueba 6 — Botón "Estado APIs"

**Dónde:** https://chat-pladi.vercel.app/admin/

**Pasos:**
1. Inicia sesión en el panel admin
2. Verifica que el botón **Estado APIs** es visible (azul, con ícono de onda)
3. Haz clic en él

**Resultado esperado:**
- ✅ Aparece un modal/popup con el diagnóstico
- ✅ `OPENAI_API_KEY`: ✓ Configurada
- ✅ `PINECONE_INDEX_HOST`: ✓ con la URL
- ✅ OpenAI → `status: OK`, dims: 1536
- ✅ Pinecone → `status: OK`, total_vectors: N (debe aumentar tras cada conversación indexada)

---

## Prueba 7 — Especialización del chat por sección

**Dónde:** https://chat-pladi.vercel.app/test.html

### 7a. Sección `financiamiento`
**Pregunta:** `Quiero financiar una cirugía de $80,000 pesos a 18 meses`

**Resultado esperado:**
- ✅ Identifica segmento B ($50,001–$150,000), tasa 30%
- ✅ Calcula o estima el pago mensual aproximado
- ✅ Explica el proceso: Simula → Solicita → Elige médico

### 7b. Sección `contacto`
**Pregunta:** `¿Cómo les escribo por WhatsApp?`

**Resultado esperado:**
- ✅ Da el número: +52 56 3231 1545
- ✅ Menciona horario: lunes–viernes 8:00–20:00
- ✅ Puede ofrecer link directo de WhatsApp

### 7c. Sección `tienda`
**Pregunta:** `¿Puedo financiar la compra de un estetoscopio?`

**Resultado esperado:**
- ✅ Confirma que sí, con crédito médico PLADIEX
- ✅ Menciona que busque el ícono ✓ Financiable en los productos

---

## Prueba 8 — Multi-sección en documento

**Dónde:** Admin → Subir archivo

**Pasos:**
1. Sube `faq-general.pdf`
2. En el selector de secciones, activa: **Financiamiento** + **Tienda** + **Servicios** (3 secciones juntas)
3. Procesa el archivo

**Resultado esperado:**
- ✅ El archivo se indexa con `sections: ["financiamiento", "tienda", "servicios"]`
- ✅ En la Biblioteca aparece el archivo con las 3 badges de sección
- ✅ Al preguntar sobre el FAQ en cualquiera de esas 3 secciones, el RAG lo encuentra
- ✅ En una sección diferente (ej. `home`), el FAQ no aparece en el contexto

---

## Checklist rápida de regresión

Después de todas las pruebas, verifica que nada regresó:

- [ ] El chat responde (no muestra "Hubo un problema de conexión")
- [ ] Los chips de sección son correctos en cada tab del test.html
- [ ] La clasificación por archivo funciona con múltiples archivos
- [ ] El botón "Estado APIs" es visible y funciona
- [ ] La biblioteca muestra los badges de sección correctamente
- [ ] Puedes eliminar un documento de la biblioteca

---

## Errores conocidos y soluciones rápidas

| Error | Causa probable | Solución |
|-------|---------------|---------|
| "Hubo un problema de conexión" | Vercel no desplegó aún | Esperar 2-3 min y reintentar |
| Los chips no cambian entre secciones | Caché del navegador | Ctrl+Shift+R para recargar sin caché |
| PDF no se procesa | PDF protegido con contraseña | Usar PDFs sin restricciones |
| El total_vectors no crece | Conversación muy corta | Enviar respuestas de más de 30 chars |
| Botón Estado APIs no visible | CSS no actualizó | Limpiar caché del navegador |

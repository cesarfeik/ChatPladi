# Marco Teórico
## Sistema de Asistente Virtual Inteligente con Generación Aumentada por Recuperación para una Plataforma de Salud Digital

---

> **Proyecto:** Chatbot RAG para PLADIEX — Plataforma de Salud Digital  
> **Tecnologías:** OpenAI GPT-4o-mini · Pinecone · Supabase · PHP · JavaScript · Vercel

---

## Índice

1. [Inteligencia Artificial](#1-inteligencia-artificial)
2. [Procesamiento de Lenguaje Natural](#2-procesamiento-de-lenguaje-natural)
3. [Modelos de Lenguaje de Gran Escala (LLM)](#3-modelos-de-lenguaje-de-gran-escala-llm)
4. [Representaciones Vectoriales (Embeddings)](#4-representaciones-vectoriales-embeddings)
5. [Bases de Datos Vectoriales](#5-bases-de-datos-vectoriales)
6. [Generación Aumentada por Recuperación (RAG)](#6-generación-aumentada-por-recuperación-rag)
7. [Asistentes Virtuales y Chatbots](#7-asistentes-virtuales-y-chatbots)
8. [Inteligencia Artificial en el Sector Salud](#8-inteligencia-artificial-en-el-sector-salud)
9. [Telemedicina y Salud Digital](#9-telemedicina-y-salud-digital)
10. [Arquitecturas de Aplicaciones Web](#10-arquitecturas-de-aplicaciones-web)
11. [Autenticación y Seguridad en Aplicaciones Web](#11-autenticación-y-seguridad-en-aplicaciones-web)
12. [Computación en la Nube y Serverless](#12-computación-en-la-nube-y-serverless)
13. [Referencias Bibliográficas](#13-referencias-bibliográficas)

---

## 1. Inteligencia Artificial

La inteligencia artificial (IA) es una rama de las ciencias computacionales que tiene como objetivo desarrollar sistemas capaces de realizar tareas que, si fueran ejecutadas por un ser humano, requerirían inteligencia (Russell y Norvig, 2020). Desde sus fundamentos teóricos establecidos por Alan Turing en 1950 con la publicación de *Computing Machinery and Intelligence*, la IA ha evolucionado significativamente, transitando desde sistemas basados en reglas explícitas hasta modelos capaces de aprender patrones complejos a partir de grandes volúmenes de datos.

McCarthy (1956) introdujo formalmente el término "inteligencia artificial" durante la conferencia de Dartmouth, donde se planteó la hipótesis de que toda característica del aprendizaje humano podía describirse con suficiente precisión para que una máquina la simulara. Décadas después, con el surgimiento del aprendizaje profundo (*deep learning*), esta hipótesis se ha materializado en aplicaciones concretas que impactan múltiples industrias, incluyendo la salud, la educación y el comercio electrónico.

En el contexto del presente proyecto, la IA se aplica específicamente mediante modelos de lenguaje que permiten a un asistente virtual comprender preguntas en lenguaje natural, recuperar información relevante de una base de conocimiento y generar respuestas coherentes y contextualizadas para los usuarios de la plataforma PLADIEX.

---

## 2. Procesamiento de Lenguaje Natural

El Procesamiento de Lenguaje Natural (PLN), o *Natural Language Processing* (NLP) en inglés, es la subdisciplina de la inteligencia artificial que estudia la interacción entre computadoras y el lenguaje humano. Su objetivo es dotar a las máquinas de la capacidad de leer, comprender, interpretar y generar texto en lenguajes naturales (Jurafsky y Martin, 2023).

El PLN engloba diversas tareas computacionales, entre las que destacan:

- **Análisis sintáctico:** Descomposición gramatical de oraciones.
- **Análisis semántico:** Interpretación del significado de palabras y frases en contexto.
- **Reconocimiento de entidades nombradas:** Identificación de personas, lugares, organizaciones y conceptos en texto.
- **Clasificación de texto:** Categorización automática de documentos según su contenido.
- **Generación de texto:** Producción automática de texto coherente y contextualmente apropiado.
- **Traducción automática:** Conversión de texto entre distintos idiomas.

El avance más significativo en PLN ocurrió con la introducción de la arquitectura *Transformer* por Vaswani et al. (2017) en el artículo *"Attention Is All You Need"*. Esta arquitectura, basada en mecanismos de atención (*self-attention*), superó a las redes neuronales recurrentes (RNN) en múltiples tareas de PLN al permitir el procesamiento paralelo de secuencias largas de texto, capturando dependencias de largo alcance con mayor eficiencia computacional.

Los *Transformers* sentaron las bases tecnológicas para el desarrollo de modelos de lenguaje como BERT (Devlin et al., 2019), GPT (Radford et al., 2018) y sus sucesores, que hoy son el núcleo de sistemas de PLN de estado del arte.

---

## 3. Modelos de Lenguaje de Gran Escala (LLM)

Los Modelos de Lenguaje de Gran Escala (*Large Language Models*, LLM) son redes neuronales profundas entrenadas sobre corpus masivos de texto con el objetivo de aprender las distribuciones probabilísticas del lenguaje natural. Su característica principal es la capacidad de generar texto coherente, responder preguntas, resumir documentos, traducir y realizar diversas tareas lingüísticas sin haber sido entrenados específicamente para cada una de ellas (Brown et al., 2020).

### 3.1 Arquitectura GPT

La familia de modelos GPT (*Generative Pre-trained Transformer*), desarrollada por OpenAI, utiliza exclusivamente el decodificador de la arquitectura Transformer para predecir el siguiente token en una secuencia de texto. El preentrenamiento se realiza sobre cientos de miles de millones de parámetros usando datos de internet, libros y documentos académicos. Posteriormente, se aplica ajuste fino mediante retroalimentación humana (RLHF, *Reinforcement Learning from Human Feedback*) para alinear el comportamiento del modelo con instrucciones y valores humanos (Ouyang et al., 2022).

El modelo **GPT-4o-mini**, utilizado en el presente proyecto, es una versión optimizada de GPT-4o que mantiene alta capacidad de razonamiento y generación de texto con un costo computacional significativamente reducido. Según OpenAI (2024), GPT-4o-mini es idóneo para aplicaciones de producción que requieren velocidad de respuesta y eficiencia económica, procesando aproximadamente 128,000 tokens de contexto con un costo de entrada de $0.15 USD por millón de tokens.

### 3.2 Capacidades relevantes para el proyecto

En el contexto del asistente virtual PLADIEX, el modelo GPT-4o-mini se emplea para:

1. **Comprensión de intenciones:** Interpretar la pregunta del usuario en español con todas sus variaciones coloquiales y médicas.
2. **Síntesis de información:** Consolidar múltiples fragmentos de documentos recuperados en una respuesta cohesionada.
3. **Generación contextualizada:** Producir respuestas empáticas y profesionales acordes al ámbito de la salud.
4. **Mantenimiento de historial:** Conservar coherencia a lo largo de una conversación de hasta 10 turnos previos.

La temperatura de generación se configuró en 0.4, valor que equilibra creatividad con precisión factual, y el límite de tokens de respuesta se estableció en 600 para mantener respuestas concisas y legibles en la interfaz del chat.

---

## 4. Representaciones Vectoriales (Embeddings)

Un *embedding* es una representación numérica densa de un objeto (palabra, frase, párrafo o documento) en un espacio vectorial de alta dimensionalidad, donde la proximidad geométrica entre vectores refleja similitud semántica (Mikolov et al., 2013). A diferencia de representaciones dispersas como *bag-of-words* o TF-IDF, los embeddings capturan relaciones semánticas y sintácticas complejas en un espacio continuo.

### 4.1 Evolución de los embeddings

La evolución de las representaciones vectoriales puede dividirse en tres generaciones:

| Generación | Modelo | Año | Característica |
|---|---|---|---|
| 1ª | Word2Vec (Mikolov et al.) | 2013 | Vectores estáticos por palabra |
| 1ª | GloVe (Pennington et al.) | 2014 | Factorización de co-ocurrencias |
| 2ª | ELMo (Peters et al.) | 2018 | Vectores contextuales (LSTM) |
| 3ª | BERT (Devlin et al.) | 2019 | Vectores contextuales (Transformer) |
| 3ª | text-embedding-3 (OpenAI) | 2024 | Embeddings para RAG y búsqueda semántica |

### 4.2 text-embedding-3-small

El modelo **text-embedding-3-small** de OpenAI, utilizado en este proyecto, genera vectores de 1,536 dimensiones para cualquier texto de entrada. Según OpenAI (2024), este modelo supera a versiones anteriores en tareas de búsqueda semántica, clasificación y recuperación de información, con un costo de $0.02 USD por millón de tokens — significativamente más económico que alternativas comparables.

La similitud entre dos vectores se calcula mediante la **similitud coseno**:

```
sim(A, B) = (A · B) / (‖A‖ × ‖B‖)
```

Donde valores cercanos a 1.0 indican alta similitud semántica y valores cercanos a 0 indican escasa relación. Esta métrica es el fundamento de la búsqueda semántica en el sistema RAG del presente proyecto.

---

## 5. Bases de Datos Vectoriales

Una base de datos vectorial (*vector database*) es un sistema de gestión de datos especializado en el almacenamiento, indexación y recuperación eficiente de vectores de alta dimensionalidad (Douze et al., 2024). A diferencia de las bases de datos relacionales que indexan datos estructurados mediante B-trees, las bases de datos vectoriales utilizan estructuras como HNSW (*Hierarchical Navigable Small World graphs*) o IVF (*Inverted File Index*) para realizar búsquedas aproximadas de vecinos más cercanos (ANN, *Approximate Nearest Neighbor*) en tiempo sublineal.

### 5.1 Pinecone

**Pinecone** es una base de datos vectorial administrada en la nube, diseñada específicamente para aplicaciones de búsqueda semántica e inteligencia artificial (Pinecone Systems, 2023). Sus características principales incluyen:

- **Búsqueda de baja latencia:** Consultas en milisegundos sobre millones de vectores.
- **Escalabilidad horizontal:** Arquitectura serverless que escala automáticamente con la demanda.
- **Namespaces:** Particionamiento lógico del índice para aislar colecciones de documentos.
- **Metadata filtering:** Filtrado adicional por metadatos adjuntos a cada vector.
- **Upsert y delete:** Operaciones CRUD sobre vectores individuales o en lotes.

En el presente proyecto, Pinecone almacena los vectores de los fragmentos de documentos indexados bajo el namespace `pladiex-docs`. Cada vector incluye los metadatos `text` (contenido del fragmento), `filename` (nombre del PDF fuente), `chunk` (índice del fragmento) y `source` (origen del documento).

### 5.2 Parámetros del índice

| Parámetro | Valor | Justificación |
|---|---|---|
| Dimensiones | 1,536 | Dimensionalidad de text-embedding-3-small |
| Métrica | Cosine | Óptima para similitud semántica |
| Tipo | Serverless | Sin gestión de infraestructura |
| topK | 5 | Recuperar los 5 fragmentos más relevantes |

---

## 6. Generación Aumentada por Recuperación (RAG)

La Generación Aumentada por Recuperación (*Retrieval-Augmented Generation*, RAG) es una arquitectura de sistemas de IA que combina la recuperación de información (*information retrieval*) con modelos generativos de lenguaje para producir respuestas fundamentadas en fuentes de conocimiento específicas y verificables (Lewis et al., 2020).

El artículo seminal de Lewis et al. (2020), *"Retrieval-Augmented Generation for Knowledge-Intensive NLP Tasks"*, publicado en NeurIPS 2020, demostró que los LLMs combinados con recuperación dinámica de documentos superan significativamente a los LLMs puros en tareas que requieren conocimiento factual preciso y actualizable.

### 6.1 Motivación

Los LLMs presentan tres limitaciones fundamentales que el RAG resuelve:

1. **Conocimiento estático:** Los LLMs solo conocen información presente en sus datos de entrenamiento, con fecha de corte fija. No pueden acceder a información actualizada o privada de una organización.
2. **Alucinaciones:** Los LLMs tienden a generar información plausible pero incorrecta cuando no tienen certeza sobre un dato factual (Ji et al., 2023).
3. **Opacidad:** Es difícil rastrear la fuente de una respuesta generada por un LLM puro.

RAG resuelve estas limitaciones al inyectar contexto recuperado dinámicamente en el prompt del LLM, permitiendo respuestas basadas en documentos reales y verificables.

### 6.2 Arquitectura del sistema RAG implementado

El sistema RAG del presente proyecto se compone de dos fases:

#### Fase de Indexación (offline)
```
Documento PDF
  │
  ▼
PDF.js extrae el texto en el navegador del administrador
  │
  ▼
División en fragmentos de 500 palabras (overlap de 50 palabras)
  │
  ▼
Cada fragmento → OpenAI text-embedding-3-small → vector de 1,536 dimensiones
  │
  ▼
Upsert de vectores en Pinecone con metadata (text, filename, chunk)
```

#### Fase de Consulta (en tiempo real)
```
Pregunta del usuario (texto libre en español)
  │
  ▼
OpenAI text-embedding-3-small → vector de la pregunta
  │
  ▼
Búsqueda semántica en Pinecone (topK=5) → fragmentos más relevantes
  │
  ▼
Construcción del prompt:
  [System: rol del asistente + reglas de comportamiento + fragmentos recuperados]
  [Historial: últimos 10 turnos de conversación]
  [User: pregunta actual]
  │
  ▼
GPT-4o-mini genera respuesta contextualizada
  │
  ▼
Respuesta + botones de acceso directo detectados automáticamente
```

### 6.3 Chunking y solapamiento

La fragmentación (*chunking*) del texto es un aspecto crítico en sistemas RAG. El tamaño del fragmento (chunk) determina la granularidad del conocimiento recuperado. Fragmentos muy pequeños pueden carecer de contexto suficiente; fragmentos muy grandes pueden diluir la relevancia semántica. 

El solapamiento (*overlap*) entre fragmentos consecutivos asegura que el contexto no se pierda en los bordes de los fragmentos. En el presente proyecto se utilizó un tamaño de 500 palabras con solapamiento de 50 palabras, configuración que ha demostrado buenos resultados en documentos de tipo informativo y técnico según Gao et al. (2023).

---

## 7. Asistentes Virtuales y Chatbots

Un chatbot es un sistema de software diseñado para simular conversación con usuarios humanos, especialmente mediante texto, con el objetivo de resolver consultas, proporcionar información o ejecutar acciones (Dale, 2016). La evolución histórica de los chatbots puede clasificarse en tres generaciones:

### 7.1 Generaciones de chatbots

**Primera generación — Basados en reglas (1966-2000s)**  
El primer chatbot documentado fue ELIZA (Weizenbaum, 1966), desarrollado en el MIT, que simulaba conversación mediante patrones de coincidencia de texto y respuestas predefinidas. ALICE (Wallace, 1995) extendió este enfoque con el lenguaje AIML (*Artificial Intelligence Markup Language*). Estos sistemas carecen de comprensión real y fallan ante variaciones no previstas en las preguntas.

**Segunda generación — Basados en recuperación (2010s)**  
Sistemas como los desarrollados sobre Apache OpenNLP o Rasa utilizan modelos de clasificación de intenciones (*intent classification*) y extracción de entidades (*entity extraction*) para mapear mensajes del usuario a respuestas predefinidas en una base de conocimiento. Son más robustos que las reglas pero limitados al dominio para el que fueron entrenados.

**Tercera generación — Basados en LLMs (2020s-presente)**  
Los chatbots basados en LLMs, como el asistente desarrollado en este proyecto, comprenden lenguaje natural con alta precisión, mantienen contexto de conversación, generan respuestas originales y pueden combinarse con RAG para fundamentar sus respuestas en conocimiento específico del dominio (Zhao et al., 2023).

### 7.2 Asistente Virtual Alex Ciencia

El asistente virtual desarrollado para PLADIEX recibe el nombre de **Alex Ciencia** y está diseñado como un agente conversacional de tercera generación con las siguientes capacidades:

- **Comprensión multiintención:** Responde preguntas sobre servicios, síntomas, citas, financiamiento y educación dentro de un mismo contexto de conversación.
- **Orientación en pre-diagnóstico:** Guía al usuario sobre posibles causas de síntomas, con la aclaración explícita de que no reemplaza consulta médica.
- **Navegación contextual:** Detecta intenciones de navegación (visitar la tienda, agendar cita, etc.) y presenta botones de acceso directo con descripción del destino.
- **Memoria de sesión:** Mantiene coherencia en el historial de los últimos 10 turnos de conversación.

---

## 8. Inteligencia Artificial en el Sector Salud

La aplicación de la inteligencia artificial en el sector salud ha experimentado un crecimiento exponencial en la última década. Según un reporte de McKinsey Global Institute (2023), la IA en salud podría generar hasta $100 mil millones de dólares anuales en valor a través de mejoras en diagnóstico, eficiencia operativa y personalización de tratamientos.

### 8.1 Aplicaciones principales

**Diagnóstico asistido por IA:** Sistemas como Google DeepMind's AlphaFold (Jumper et al., 2021) han revolucionado la predicción de estructuras proteicas, mientras que modelos de visión computacional han alcanzado rendimiento de nivel experto en la detección de patologías en imágenes médicas (Esteva et al., 2017).

**Procesamiento de registros médicos:** Los LLMs han demostrado capacidad para extraer información estructurada de notas clínicas en lenguaje natural, facilitando la investigación clínica y la gestión del expediente electrónico (Singhal et al., 2023).

**Asistentes virtuales médicos:** La OMS (2021) reporta que los chatbots en salud han sido desplegados en más de 50 países para triaje de síntomas, recordatorios de medicación y educación en salud, especialmente durante la pandemia de COVID-19.

### 8.2 Consideraciones éticas

El uso de IA en salud implica consideraciones éticas fundamentales que el presente proyecto atiende:

- **No reemplaza al médico:** El asistente está programado para ofrecer orientación general y siempre derivar al usuario a consulta médica profesional para diagnóstico y tratamiento.
- **Transparencia:** El sistema indica explícitamente que es un asistente virtual, no un profesional médico.
- **Privacidad:** Los documentos indexados no contienen información personal de pacientes; corresponden a información institucional de PLADIEX.
- **Limitaciones explícitas:** El footer del widget indica permanentemente: *"Impulsado por IA · No reemplaza consulta médica"*.

Estas prácticas se alinean con los principios de IA responsable establecidos por la UNESCO (2022) en su *Recomendación sobre la Ética de la Inteligencia Artificial*.

---

## 9. Telemedicina y Salud Digital

La telemedicina se define como la prestación de servicios de salud a través de tecnologías de la información y la comunicación, cuando el proveedor de salud y el paciente no se encuentran en el mismo lugar (OMS, 2010). Su adopción se aceleró significativamente durante la pandemia de COVID-19 (2020-2022), estableciendo nuevos modelos de atención médica que combinan consultas presenciales con atención remota.

### 9.1 Plataformas de salud digital

Las plataformas de salud digital (*digital health platforms*) integran múltiples servicios de atención médica en un entorno unificado. PLADIEX es un ejemplo de este modelo, ofreciendo:

- Directorio médico y gestión de citas presenciales y teleconsultas.
- Expediente clínico digital.
- Tienda de productos farmacéuticos y dispositivos médicos.
- Financiamiento médico (*QuickLease*).
- Plataforma de capacitación y e-learning para profesionales de la salud.

La incorporación de un asistente virtual inteligente como Alex Ciencia responde a la tendencia global de aumentar el acceso a información de salud de calidad mediante tecnología, reduciendo barreras geográficas, económicas y de tiempo que limitan el acceso a atención médica en México y América Latina.

---

## 10. Arquitecturas de Aplicaciones Web

### 10.1 PHP — Lenguaje de backend

PHP (*Hypertext Preprocessor*) es un lenguaje de scripting interpretado, diseñado originalmente para el desarrollo web dinámico del lado del servidor. Actualmente, en su versión 8.x, incorpora características de programación orientada a objetos, tipos declarativos y JIT (*Just-In-Time Compiler*), lo que lo posiciona como un lenguaje maduro para aplicaciones web de producción (PHP Group, 2023).

La elección de PHP en el presente proyecto responde a su compatibilidad universal con servidores de hospedaje compartido (como Hostinger, el proveedor de pladiex.com), su facilidad de integración con APIs REST mediante cURL, y su capacidad de ejecutarse como función serverless en plataformas modernas mediante runtimes como `vercel-php`.

### 10.2 JavaScript — Widget embebible

El widget del chatbot está implementado en JavaScript puro (*Vanilla JS*), sin dependencias de frameworks como React o Vue. Esta decisión de diseño responde al principio de **mínima intrusión**: el widget debe poderse añadir a cualquier sitio web existente con una sola línea de código, sin afectar el rendimiento ni requerir cambios en el stack tecnológico del sitio anfitrión.

El widget utiliza el patrón IIFE (*Immediately Invoked Function Expression*) para encapsular todo su código y evitar colisiones con variables del sitio donde se integra. Los estilos se cargan dinámicamente mediante un elemento `<link>` insertado en el `<head>` del documento.

### 10.3 PDF.js — Extracción de texto en el cliente

PDF.js es una librería de código abierto desarrollada por Mozilla Foundation que permite renderizar y extraer contenido de archivos PDF directamente en el navegador web, sin necesidad de plugins ni procesamiento en el servidor (Mozilla Foundation, 2011).

En el panel de administración, PDF.js se utiliza para extraer el texto de los documentos PDF antes de enviarlo al servidor PHP. Esta arquitectura ofrece ventajas significativas:

- El servidor nunca recibe el archivo binario del PDF, reduciendo el consumo de ancho de banda.
- No se requieren extensiones PHP para manejo de PDFs (como `pdf2text` o `fpdf`).
- El procesamiento de texto es compatible con cualquier tipo de servidor PHP.

### 10.4 API REST

El sistema sigue el patrón arquitectónico REST (*Representational State Transfer*) para la comunicación entre el widget JavaScript y los endpoints PHP. Cada operación tiene un endpoint dedicado con el método HTTP apropiado:

| Endpoint | Método | Función |
|---|---|---|
| `/api/chat.php` | POST | Enviar mensaje y recibir respuesta del bot |
| `/api/process-pdf.php` | POST | Indexar documento PDF en Pinecone |
| `/api/delete-document.php` | DELETE | Eliminar documento de Pinecone y Supabase |

---

## 11. Autenticación y Seguridad en Aplicaciones Web

### 11.1 JSON Web Tokens (JWT)

JSON Web Token (JWT) es un estándar abierto (RFC 7519) que define un mecanismo compacto y autónomo para transmitir información entre partes como un objeto JSON, firmado digitalmente para verificar su autenticidad e integridad (Jones et al., 2015).

Un JWT se compone de tres partes codificadas en Base64URL y separadas por puntos:

```
header.payload.signature
```

- **Header:** Algoritmo de firma (ej. HS256) y tipo de token.
- **Payload:** Claims — datos del usuario (id, email, rol, fecha de expiración).
- **Signature:** HMAC del header y payload con la clave secreta del servidor.

En el sistema PLADIEX, los JWTs son emitidos por Supabase Auth al momento del inicio de sesión del administrador. El token se envía en el header `Authorization: Bearer <token>` en cada petición al API PHP, donde se valida el emisor (*iss*) y la expiración (*exp*) del token para autorizar operaciones privilegiadas.

### 11.2 Supabase — Backend as a Service

Supabase es una plataforma de desarrollo que provee base de datos PostgreSQL administrada, autenticación, almacenamiento de archivos y APIs en tiempo real (Supabase Inc., 2020). Se posiciona como alternativa de código abierto a Firebase.

En el presente proyecto, Supabase se utiliza para:

- **Autenticación:** Gestión de las cuentas de los tres administradores del panel. Los usuarios se crean mediante invitación, garantizando acceso controlado.
- **Base de datos:** Tabla `documents` con políticas RLS (*Row Level Security*) que permiten a cualquier usuario autenticado leer, insertar y eliminar registros propios.

### 11.3 CORS y seguridad de APIs

El Cross-Origin Resource Sharing (CORS) es un mecanismo de seguridad del navegador que restringe las peticiones HTTP realizadas desde un origen diferente al del servidor que responde (W3C, 2014). Los endpoints PHP del sistema incluyen headers CORS configurados mediante la variable de entorno `ALLOWED_ORIGIN`, que en producción deberá restringirse al dominio de pladiex.com.

---

## 12. Computación en la Nube y Serverless

### 12.1 Computación en la nube

La computación en la nube (*cloud computing*) es el modelo de prestación de servicios computacionales (servidores, almacenamiento, bases de datos, redes, software) a través de internet bajo demanda y con facturación por uso (NIST, 2011). El Instituto Nacional de Estándares y Tecnología (NIST) identifica cinco características esenciales: autoservicio bajo demanda, acceso amplio a red, agrupación de recursos, elasticidad rápida y servicio medido.

### 12.2 Funciones Serverless

El modelo *serverless* (sin servidor, aunque los servidores existen pero son abstraídos) permite a los desarrolladores desplegar código sin gestionar infraestructura. Las funciones son ejecutadas bajo demanda, escalan automáticamente y se facturan únicamente por el tiempo de ejecución (Roberts, 2018).

**Vercel** es una plataforma de despliegue en la nube especializada en aplicaciones web modernas. Ofrece:

- Despliegue continuo integrado con GitHub (push a main → redeploy automático).
- CDN global para archivos estáticos con tiempos de respuesta de ~30ms.
- Funciones serverless con soporte para Node.js, Python, Go y PHP (mediante runtime comunitario).
- Entornos de variables para gestión segura de credenciales.
- Plan gratuito suficiente para el volumen de uso estimado del proyecto.

La elección de Vercel para el despliegue del presente proyecto responde a su integración nativa con GitHub, la gratuidad del plan inicial y la compatibilidad con el runtime PHP serverless (`vercel-php@0.7.2`), que permite ejecutar los endpoints PHP del sistema sin necesidad de un servidor dedicado.

---

## 13. Referencias Bibliográficas

Brown, T., Mann, B., Ryder, N., Subbiah, M., Kaplan, J., Dhariwal, P., ... y Amodei, D. (2020). *Language Models are Few-Shot Learners*. Advances in Neural Information Processing Systems, 33, 1877-1901.

Dale, R. (2016). The return of the chatbots. *Natural Language Engineering*, 22(5), 811-817. https://doi.org/10.1017/S1351324916000243

Devlin, J., Chang, M. W., Lee, K., y Toutanova, K. (2019). *BERT: Pre-training of Deep Bidirectional Transformers for Language Understanding*. Proceedings of NAACL-HLT 2019, 4171-4186.

Douze, M., Guzhva, A., Deng, C., Johnson, J., Szilvasy, G., Mazaré, P. E., ... y Jégou, H. (2024). *The Faiss library*. arXiv preprint arXiv:2401.08281.

Esteva, A., Kuprel, B., Novoa, R. A., Ko, J., Swetter, S. M., Blau, H. M., y Thrun, S. (2017). Dermatologist-level classification of skin cancer with deep neural networks. *Nature*, 542(7639), 115-118.

Gao, Y., Xiong, Y., Gao, X., Jia, K., Pan, J., Bi, Y., ... y Wang, H. (2023). *Retrieval-Augmented Generation for Large Language Models: A Survey*. arXiv preprint arXiv:2312.10997.

Ji, Z., Lee, N., Frieske, R., Yu, T., Su, D., Xu, Y., ... y Fung, P. (2023). Survey of Hallucination in Natural Language Generation. *ACM Computing Surveys*, 55(12), 1-38.

Jones, M., Bradley, J., y Sakimura, N. (2015). *JSON Web Token (JWT)*. RFC 7519. Internet Engineering Task Force. https://tools.ietf.org/html/rfc7519

Jurafsky, D., y Martin, J. H. (2023). *Speech and Language Processing* (3ra edición, borrador). Stanford University. https://web.stanford.edu/~jurafsky/slp3/

Jumper, J., Evans, R., Pritzel, A., Green, T., Figurnov, M., Ronneberger, O., ... y Hassabis, D. (2021). Highly accurate protein structure prediction with AlphaFold. *Nature*, 596(7873), 583-589.

Lewis, P., Perez, E., Piktus, A., Petroni, F., Karpukhin, V., Goyal, N., ... y Kiela, D. (2020). *Retrieval-Augmented Generation for Knowledge-Intensive NLP Tasks*. Advances in Neural Information Processing Systems, 33, 9459-9474.

McCarthy, J., Minsky, M. L., Rochester, N., y Shannon, C. E. (1956). *A proposal for the Dartmouth summer research project on artificial intelligence*. Dartmouth College.

McKinsey Global Institute. (2023). *The economic potential of generative AI: The next productivity frontier*. McKinsey & Company.

Mikolov, T., Chen, K., Corrado, G., y Dean, J. (2013). *Efficient Estimation of Word Representations in Vector Space*. Proceedings of ICLR 2013. arXiv:1301.3781.

Mozilla Foundation. (2011). *PDF.js — A general-purpose, web standards-based platform for parsing and rendering PDFs*. https://mozilla.github.io/pdf.js/

NIST. (2011). *The NIST Definition of Cloud Computing*. Special Publication 800-145. National Institute of Standards and Technology. https://doi.org/10.6028/NIST.SP.800-145

OpenAI. (2024). *GPT-4o mini: advancing cost-efficient intelligence*. OpenAI Blog. https://openai.com/index/gpt-4o-mini-advancing-cost-efficient-intelligence/

Organización Mundial de la Salud (OMS). (2010). *Telemedicine: Opportunities and developments in Member States*. WHO Press.

Organización Mundial de la Salud (OMS). (2021). *Ethics and governance of artificial intelligence for health*. WHO Press. https://www.who.int/publications/i/item/9789240029200

Ouyang, L., Wu, J., Jiang, X., Almeida, D., Wainwright, C. L., Mishkin, P., ... y Lowe, R. (2022). *Training language models to follow instructions with human feedback*. arXiv preprint arXiv:2203.02155.

PHP Group. (2023). *PHP: Hypertext Preprocessor — Manual*. https://www.php.net/manual/es/

Pinecone Systems. (2023). *Pinecone Documentation — Vector Database for Machine Learning Applications*. https://docs.pinecone.io/

Roberts, M. (2018). *Serverless Architectures*. Martin Fowler Blog. https://martinfowler.com/articles/serverless.html

Russell, S. J., y Norvig, P. (2020). *Artificial Intelligence: A Modern Approach* (4ta edición). Pearson.

Singhal, K., Azizi, S., Tu, T., Mahdavi, S. S., Wei, J., Chung, H. W., ... y Natarajan, V. (2023). Large language models encode clinical knowledge. *Nature*, 620(7972), 172-180.

Supabase Inc. (2020). *Supabase Documentation — The Open Source Firebase Alternative*. https://supabase.com/docs

Turing, A. M. (1950). Computing Machinery and Intelligence. *Mind*, 59(236), 433-460.

UNESCO. (2022). *Recommendation on the Ethics of Artificial Intelligence*. United Nations Educational, Scientific and Cultural Organization. https://unesdoc.unesco.org/ark:/48223/pf0000381137

Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., ... y Polosukhin, I. (2017). *Attention Is All You Need*. Advances in Neural Information Processing Systems, 30.

W3C. (2014). *Cross-Origin Resource Sharing*. W3C Recommendation. https://www.w3.org/TR/cors/

Weizenbaum, J. (1966). ELIZA—a computer program for the study of natural language communication between man and machine. *Communications of the ACM*, 9(1), 36-45.

Zhao, W. X., Zhou, K., Li, J., Tang, T., Wang, X., Hou, Y., ... y Wen, J. R. (2023). *A Survey of Large Language Models*. arXiv preprint arXiv:2303.18223.

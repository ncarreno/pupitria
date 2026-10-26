# Arquitectura de la v1

*Octubre 2026 · Copia actualizada del documento "Arquitectura de la v1" del proyecto de Claude.*

## Decisiones
- **Stack:** Laravel 13 + Livewire 3 (Volt) + Alpine.js + Tailwind CSS. Desarrollo local con Laravel Herd y MySQL. Laravel Boost instalado.
- **IA configurable:** Claude, ChatGPT (OpenAI), Gemini y otros, a través del **Laravel AI SDK** (`laravel/ai`).
- **Solo el administrador (Nacho) elige la IA**, para toda la aplicación. Los docentes no ven ni eligen el proveedor.
  - **Excepción (26 oct):** en cada ficha, la docente elige si la ilustración la crea la app con OpenAI o si recibe un prompt para pegarlo en ChatGPT o Gemini y subir la imagen. Solo afecta a la imagen.
- **Imágenes con la API de OpenAI**, porque Claude no genera imágenes.
- **Base de datos:** MySQL/MariaDB.
- **Alojamiento:** un servidor Plesk propio en España o la UE.
- **Sin datos personales de alumnos** en la v1.

## Módulos
| Módulo | Qué hace |
|---|---|
| **Cuentas** | Registro e inicio de sesión de docentes (starter kit de Livewire), con verificación del email obligatoria antes de usar la app. Rol docente o administrador. Preferencias: estilo visual por defecto. |
| **Mis clases** | Varias clases por docente: curso, número de alumnos, áreas, curso escolar y cuántos necesitan refuerzo o ampliación. |
| **Currículo** | Decreto 57/2022 en tablas propias, de solo lectura y versionadas por normativa. Navegable por área y ciclo. |
| **Proyectos** | Generar, ajustar con instrucciones cortas, editar en línea, historial de versiones y duplicar. Alcance de aula o de centro (itinerario 1.º–6.º). |
| **Fichas** | Generar en 3 niveles a partir de una tarea del proyecto o de un tema libre. Edición en línea. Ilustración opcional (OpenAI o prompt externo). |
| **Exportación** | Word (.docx) editable y PDF de calidad, con los estilos visuales. |
| **Biblioteca** | Listado de proyectos y fichas, búsqueda y filtros por área, criterio y curso. |
| **Administración de IA** | Solo para el administrador: activar proveedores, elegir modelo por tarea, definir el orden de respaldo y ver costes. |
| **Control de uso** | Registro de cada generación (proveedor, modelo, tokens, duración, errores) para controlar y comparar costes y, más adelante, poner límites al plan gratuito. |

## Proveedores de IA

### Librería
**Laravel AI SDK** (`laravel/ai`). Según su documentación:
- Admite, entre otros, Anthropic (Claude), OpenAI (ChatGPT), Gemini, Azure, Groq, xAI, DeepSeek, Mistral y Ollama.
- Permite elegir proveedor y modelo por llamada y en tiempo de ejecución.
- Salida estructurada con esquema JSON (`HasStructuredOutput`).
- Respaldo automático (failover) entre una lista de proveedores.
- Imágenes (`Image::of()->generate()`) con OpenAI, Gemini o xAI.
- Funciona con colas y con streaming.

### Diseño
- **Una sola capa de generación** (`ClienteIA`, usada por `GeneradorProyecto` y `GeneradorFicha`) que no sabe qué proveedor hay debajo. Prompts, esquema y validación son los mismos para todos.
- **Configuración por tarea** (`proyecto`, `ajuste`, `ficha`, `prueba`, `imagen`): proveedor, modelo y lista de respaldo.
- **Tabla `ia_configuraciones`**: tarea, proveedor, modelo (null = por defecto), orden (1 = principal, 2+ = respaldo), timeout y activo. Única por (tarea, proveedor). Se editará desde el panel de administración (sprint 4).
- **Claves de API en `.env`**, una por proveedor. Los docentes nunca las ven.
- **Registro en `generaciones`**: usuario, tarea, estado, proveedor, modelo, si respondió el respaldo, tokens (entrada, salida, caché), duración, ids descartados y error. **Nunca el texto de la petición.**
- **Preparado para más adelante:** una preferencia por usuario que sustituya la configuración global. No se construye ahora.

### Configuración inicial (seeder `IaConfiguracionSeeder`)
| Tarea | Proveedor | Modelo | Timeout |
|---|---|---|---|
| proyecto | anthropic | por defecto | 240 s |
| ajuste, ficha | anthropic | por defecto | 180 s |
| prueba | anthropic | por defecto | 60 s |
| imagen | openai | por defecto | 120 s |

### Lo que no cambia según el proveedor
La **validación curricular es independiente de la IA**: criterios y saberes se comprueban contra el decreto y competencias y descriptores se calculan. Ningún proveedor puede colar un criterio inventado.

### Riesgos
- **Calidad y tono cambian entre modelos.** El "suena menos a IA" se validó con Claude. Cada proveedor nuevo debe pasar la **batería de pruebas** (sprint 4): los mismos 5–6 casos reales comparados lado a lado, idealmente valorados por la beta tester.
- **La salida estructurada no es idéntica en todos los proveedores.** Se mantiene el esquema simple (por eso las fichas usan un único esquema de bloque para los 8 tipos).
- **La caché de prompts funciona distinto en cada proveedor.** El currículo siempre va al principio del prompt.
- **Privacidad:** revisar las condiciones de cada proveedor antes de activarlo para otros docentes.

## Imágenes
- **`ClienteIA::generarImagen()`**: genera (cuadrada, vertical o apaisada), guarda en el disco `public` (`imagenes/{usuario}/`) y registra la generación. Tarea `imagen` en `ia_configuraciones`.
- **Nunca texto dentro de la imagen** (`ClienteIA::SIN_TEXTO` se añade a cada petición). Los textos van en HTML editable.
- **Fichas:** `fichas.imagen_por` = `openai` | `prompt`. Con ilustración se guarda siempre `prompt_imagen`; en modo prompt (o si OpenAI falla) la docente lo copia y sube la imagen desde `/fichas/{id}`.
- **Contra el "todas parecen iguales":** la ilustración es un complemento; el estilo lo dan los temas visuales de la app.
- **Comando de prueba:** `php artisan ia:probar-imagen`. Requiere `OPENAI_API_KEY` y `php artisan storage:link`.

## Modelo de datos
**Currículo (solo lectura, desde `database/data/curriculo-primaria-asturias.json`)**
- `normativas`, `areas`, `competencias_especificas`, `criterios` (id estable `MAT.C2.1.1`), `saberes` (id estable `CN.C2.A.2.4`), `descriptores` y pivote `competencia_descriptor`. 10 áreas, 58 competencias, 429 criterios, 865 saberes, 34 descriptores.
- `curriculo:importar` actualiza lo que cambie en el JSON. Lo que ya no está solo se avisa y se borra con `--borrar-sobrantes`. Antes de usarlo, mira si algún proyecto o ficha cita esos ids: al borrar un criterio o un saber se quita también de sus proyectos y fichas.
- **Una sola normativa.** Las consultas buscan las áreas por `codigo` sin filtrar por normativa, y `competencias_especificas.codigo` es único en toda la tabla. Vale mientras solo esté Asturias (otras comunidades quedan fuera de la v1). Para añadir otra hará falta guardar la normativa en la clase, filtrar por ella y que el código de competencia sea único por área.

**Datos de cada docente**
- `users`: nombre, email, contraseña, preferencias, rol (docente o administrador).
- `clases` + pivote `area_clase`.
- `proyectos` (contenido JSON, estado, error, ids descartados) + `proyecto_versiones` + pivotes `criterio_proyecto` y `proyecto_saber`.
- `fichas`: clase, proyecto y tarea opcionales, área, curso, tema, título, `contenido` (JSON con los 3 niveles), `con_imagen`, `imagen_por`, `imagen`, `prompt_imagen`, estado, error, ids descartados. Pivote `criterio_ficha`.
- `ia_configuraciones` y `generaciones`.

## Flujo de generación
1. Livewire crea el registro (proyecto o ficha) en estado `generando` y lanza un **job en cola**.
2. El job construye el prompt con plantillas Blade en `resources/views/prompts/`: currículo del ciclo y las áreas al principio, contexto de la clase y reglas de estilo.
3. `ClienteIA` lee la configuración de la tarea y llama al SDK con proveedor, modelo, respaldo y esquema JSON.
4. **Validación (igual para todos):** ids contra la base de datos; competencias, competencias clave y descriptores calculados.
5. Se guarda el resultado y su versión. La interfaz consulta el estado cada 3 s (`wire:poll`).
6. **Errores:** el respaldo cubre caídas y límites de tasa. Si falla la primera generación, queda en error con "Volver a intentarlo"; si falla un ajuste, se conserva la versión anterior.

## Exportación (sprint 5)
- **Word (.docx):** PhpWord con plantillas por tipo de documento.
- **PDF:** `spatie/laravel-pdf` (o Browsershot) con las mismas vistas Blade que la pantalla. Requiere Chromium en el servidor.
- Los 4 estilos visuales del prototipo pasan a ser **temas CSS** compartidos entre pantalla y PDF.

## Despliegue en Plesk
- PHP 8.4+ con FPM, MySQL/MariaDB y HTTPS.
- **Cola:** worker con Supervisor si hay root; si no, tarea programada de Plesk cada minuto con `queue:work --stop-when-empty`. Como puede haber varios workers a la vez, `retry_after` de la cola `database` (460 s, `DB_QUEUE_RETRY_AFTER`) debe ser mayor que el timeout más largo de los jobs; si no, otro worker coge un job que sigue en marcha y lo da por fallido.
- `php artisan storage:link`. Chromium para los PDF.
- Claves de los proveedores activos (Anthropic y OpenAI como mínimo).
- Copias diarias de la base de datos y de `storage/app/public`.
- Cloudflare delante.

## Privacidad
- No se guardan ni se envían a la IA datos de alumnos: solo curso, número de alumnos y áreas.
- Datos alojados en la UE.
- **Pendiente:** revisar las condiciones de tratamiento de datos de Anthropic y OpenAI y redactar política de privacidad y aviso legal antes de abrirla a otros docentes.

## Hoja de ruta (sprints de 1–2 semanas)
| Sprint | Contenido | Estado |
|---|---|---|
| 0 | Proyecto Laravel, cuentas y roles, importación del currículo, Laravel AI SDK | Hecho |
| 1 | Mis clases | Hecho |
| 2 | Generador de proyectos, edición en línea, versiones | Hecho (en prueba con la beta tester) |
| — | Imágenes con OpenAI (`ClienteIA::generarImagen`) | Hecho |
| 3 | Fichas en 3 niveles, ilustración con OpenAI o prompt externo | Hecho (en prueba con la beta tester) |
| 4 | Panel de administración de IA, respaldo, batería de pruebas comparativa | **Siguiente** |
| 5 | Exportación Word y PDF, estilos visuales | |
| 6 | Biblioteca, búsqueda, duplicar, control de uso | |

**Después de la v1:** currículo de Infantil, estilo propio aprendido de sus ejemplos, preparación de reuniones con familias, programación de aula y planes de pago.

## Fuentes
- [Laravel AI SDK – documentación](https://laravel.com/docs/12.x/ai-sdk)
- [laravel/ai en Packagist](https://packagist.org/packages/laravel/ai)

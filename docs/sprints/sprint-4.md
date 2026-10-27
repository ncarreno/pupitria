# Sprint 4: administración de IA y batería de pruebas comparativa

*Octubre 2026 · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 3](sprint-3.md) · **Hecho (27 oct); 126 tests en verde (PHP 8.4, Herd)***

## Objetivo
Que Nacho pueda **cambiar proveedor y modelo por tarea sin tocar la base de datos**, ver cuánto y cómo se usa la IA y, antes de cambiar de proveedor, **comparar resultados con casos reales** lado a lado.

## Hecho
- **A. Panel `/admin/ia`** (middleware `admin` y comprobación en cada acción de Livewire). Una tarjeta por tarea con sus filas: añadir proveedor, subir y bajar (el primero activo es el principal), modelo y tiempo máximo, activar y desactivar, quitar y **Probar**. Marca "Sin clave" si falta en `.env`. En ilustraciones solo deja OpenAI, Gemini y xAI.
  - No deja una tarea sin proveedor activo.
  - El tiempo máximo tiene un tope por tarea (`IaConfiguracion::TIMEOUT_MAXIMO`) para que quepa en el timeout del job.
  - `ClienteIA::generarEstructurado()` y `generarImagen()` aceptan `soloEsta` (una fila, sin respaldo) para el botón Probar. Las pruebas se registran como tarea `prueba`.
  - **Modelos sugeridos** por proveedor (`IaConfiguracion::modelosPara()`): el más barato, el de por defecto y el más potente según el Laravel AI SDK, para que sigan al día al actualizarlo. Se puede escribir cualquier otro.
- **B. Uso `/admin/ia/uso`** (solo tokens; el coste en euros está pospuesto). Periodo de 7 o 30 días. Por tarea: generaciones, % fallidas, % respondidas por el respaldo y sin terminar. Desglose por proveedor y modelo de las completadas: duración media, tokens de entrada, salida y caché, e ids descartados. Debajo, las 25 últimas generaciones con su error. Una generación fallida no tiene proveedor (fallaron todos los de la cadena), así que las fallidas se cuentan por tarea.
- **C. Respaldo:** test que confirma que, si el principal limita la tasa, responde el siguiente y `generaciones.respaldo = true`.
- **D. Batería de pruebas** (`/admin/ia/pruebas`):
  - Tablas `ia_casos`, `ia_ejecuciones` e `ia_valoraciones`. Cambio sobre el plan: la valoración va en su propia tabla, una por persona y ejecución, porque valoran Nacho y la beta tester.
  - **Permiso `users.puede_valorar`** (comando `usuarios:valorar {email}`, Gate `valorar-pruebas`): ve y valora las pruebas sin ser administradora. Lanzar, configurar y ver el uso siguen siendo solo del administrador.
  - **Lanzar:** `ia:bateria {--proveedores=anthropic,openai,gemini} {--caso=}` (admite `proveedor:modelo`) o el botón del listado. Un job `EjecutarCasoIA` por proveedor y caso. Sin modelo explícito, usa el de la configuración de la tarea o el del proveedor.
  - **Generadores reutilizados sin guardar nada:** `BateriaPruebas` monta un `Proyecto` o una `Ficha` sin guardar (la clase va como relación en memoria). Los generadores aceptan `soloEsta` para forzar el proveedor, y la llamada se registra como tarea `bateria`, que aparece aparte en el uso.
  - **Comparación** (`/admin/ia/pruebas/{caso}`): la última ejecución de cada proveedor y modelo como A, B, C… El orden se baraja de forma estable para cada persona. Se ven con los mismos componentes que la docente, con duración, tokens e ids descartados. **A ciegas** hasta que quien mira ha valorado todas; después se ven los proveedores y las valoraciones de todos.
  - **Casos** (`IaCasosSeeder`): "Exploradores del planeta", "Artes escénicas (centro)" y dos fichas **provisionales** hasta que la beta tester elija las suyas.
  - Tests en `BateriaPruebasTest`.
- **Extra: cuentas `/admin/cuentas`** (solo administrador). Lista con búsqueda: nombre, email, fecha de registro, si el email está verificado, recuentos de clases, proyectos y fichas (nunca su contenido) y permisos. Desde ahí se hace o se deja de ser administrador (salvo uno mismo), se da o se quita el permiso de valorar, se marca un email como verificado si no le llega el correo y se dan de alta cuentas ("Nueva cuenta": nombre, email, contraseña inicial generada y editable, y permisos; el email queda verificado y los datos se muestran una vez para copiarlos y pasárselos a la docente, que cambia la contraseña en su perfil; sin email de invitación mientras el correo de producción no esté configurado). Tests en `AdminCuentasTest`.
- **Extra: administración aparte de la app** (misma tabla `users` y misma sesión, pero zona separada). Entrada propia en `/admin/login`: solo deja entrar al administrador y a quien valora (que allí solo ve la batería); si hay sesión de docente, la sustituye. Sin sesión, `/admin/*` lleva ahí (`redirectGuestsTo`), y el middleware `admin` también manda ahí a una docente con sesión (antes daba 403). Layout `layouts.admin` con su menú oscuro (`layout.navegacion-admin`: Resumen, Configuración de la IA, Uso, Batería, Cuentas, "Ir a la app" y cerrar sesión, que vuelve a `/admin/login`). Portada `/admin` con recuentos de cuentas, contenido y la IA de 7 días. En el menú de la docente, el administrador solo ve "Administración". Tests en `AdminAccesoTest`.
- **Extra: rediseño de la interfaz de la app** (no de los documentos): paleta propia (`pino`) sobre `stone`, clases comunes en `resources/css/app.css` (`.contenedor`, `.boton-*`…), componentes nuevos (`x-cabecera`, `x-estado-vacio`, `x-generando`, `x-icono`, `x-marca`) y portada, panel y páginas rehechos. `x-proyecto.documento` y `x-ficha.hoja` no cambian.
- **Arreglo en fichas:** los niveles van en una lista con su nombre (`niveles: [{nivel, consigna, bloques}]`) y no como tres propiedades, para que el esquema del bloque aparezca una sola vez y la gramática que compila Anthropic no supere su límite. `GeneradorFicha` lo vuelve a guardar por nivel, así que `fichas.contenido` no cambia.
- **Vite:** `npm run dev` es ahora `vite build --watch` (el servidor de Vite no cargaba los estilos en el Chrome de Nacho); `npm run hot` es el servidor clásico.
- **Antes de empezar:** el proyecto ya está en git (`main` en GitHub) y se han borrado los `.tgz` de transferencia de `storage/app/private/`.
- Tests en `AdminIATest` y `ClienteIATest`.

## Decidido (26 oct)
- Se compara con Claude, ChatGPT y Gemini (por defecto en `ia:bateria`).
- Valoran Nacho y la beta tester, nadie más: permiso `puede_valorar`, no rol de administrador.
- Coste en euros pospuesto.

## Pendiente
- **Coste estimado en `/admin/ia/uso`** (pospuesto). Cada generación ya guarda proveedor, modelo y tokens, así que el coste se podrá calcular también para el historial. Al hacerlo:
  - Tabla de precios por modelo con la **fecha desde la que vale** cada precio, para que el historial use el precio de su momento.
  - Hoy solo se guardan los tokens **leídos** de caché, no los **escritos** (Anthropic los cobra más caros). Sin ese dato el coste sale algo por debajo del real; guardarlo pronto da más historial exacto aunque el coste espere.

## Pendiente de Nacho
- `GEMINI_API_KEY` en `.env` (local y servidor); sin ella, las ejecuciones con Gemini salen como fallidas.
- En el servidor: `php artisan migrate`, `php artisan db:seed --class=IaCasosSeeder` y `php artisan usuarios:valorar {email de la beta tester}`.
- Que la beta tester elija sus 3 o 4 fichas para sustituir las dos provisionales del seeder.

## Siguiente: [sprint 5](sprint-5.md) (exportación Word y PDF, estilos visuales)

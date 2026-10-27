# Sprint 4: administración de IA y batería de pruebas comparativa

*En curso · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 3](sprint-3.md)*

## Hecho hasta ahora (26 oct)
- **A. Panel `/admin/ia`** (middleware `admin` y comprobación en cada acción de Livewire). Una tarjeta por tarea con sus filas: añadir proveedor, subir y bajar (el primero activo es el principal), modelo y tiempo máximo, activar y desactivar, quitar y **Probar**. Marca "Sin clave" si falta en `.env`. En ilustraciones solo deja OpenAI, Gemini y xAI. Enlace "IA" en el menú solo para administradores.
  - No deja una tarea sin proveedor activo.
  - El tiempo máximo tiene un tope por tarea (`IaConfiguracion::TIMEOUT_MAXIMO`) para que quepa en el timeout del job.
  - `ClienteIA::generarEstructurado()` y `generarImagen()` aceptan `soloEsta` (una fila, sin respaldo) para el botón Probar. Las pruebas se registran como tarea `prueba`.
- **B. Uso `/admin/ia/uso`** (solo tokens; el coste en euros espera la decisión sobre la tabla de precios). Periodo de 7 o 30 días. Por tarea: generaciones, % fallidas, % respondidas por el respaldo y sin terminar. Desglose por proveedor y modelo de las completadas: duración media, tokens de entrada, salida y caché, e ids descartados. Debajo, las 25 últimas generaciones con su error. Una generación fallida no tiene proveedor (fallaron todos los de la cadena), así que las fallidas se cuentan por tarea.
- **C. Respaldo:** test que confirma que, si el principal limita la tasa, responde el siguiente y `generaciones.respaldo = true`.
- **D. Batería de pruebas** (`/admin/ia/pruebas`):
  - Tablas `ia_casos`, `ia_ejecuciones` e `ia_valoraciones`. Cambio sobre el plan: la valoración va en su propia tabla, una por persona y ejecución, porque valoran Nacho y la beta tester.
  - **Permiso `users.puede_valorar`** (comando `usuarios:valorar {email}`, Gate `valorar-pruebas`): ve y valora las pruebas sin ser administradora. Lanzar, configurar y ver el uso siguen siendo solo del administrador. Menú "Valorar pruebas" para ella.
  - **Lanzar:** `ia:bateria {--proveedores=anthropic,openai,gemini} {--caso=}` (admite `proveedor:modelo`) o el botón del listado. Un job `EjecutarCasoIA` por proveedor y caso. Sin modelo explícito, usa el de la configuración de la tarea o el del proveedor.
  - **Generadores reutilizados sin guardar nada:** `BateriaPruebas` monta un `Proyecto` o una `Ficha` sin guardar (la clase va como relación en memoria). Los generadores aceptan `soloEsta` para forzar el proveedor, y la llamada se registra como tarea `bateria`, que aparece aparte en el uso.
  - **Comparación** (`/admin/ia/pruebas/{caso}`): la última ejecución de cada proveedor y modelo como A, B, C… El orden se baraja de forma estable para cada persona. Se ven con los mismos componentes que la docente, con duración, tokens e ids descartados. **A ciegas** hasta que quien mira ha valorado todas; después se ven los proveedores y las valoraciones de todos.
  - **Casos** (`IaCasosSeeder`): "Exploradores del planeta", "Artes escénicas (centro)" y dos fichas **provisionales** hasta que la beta tester elija las suyas.
  - Tests en `BateriaPruebasTest`.
- **Extra: cuentas `/admin/cuentas`** (solo administrador; menú "Cuentas"). Lista con búsqueda: nombre, email, fecha de registro, si el email está verificado, recuentos de clases, proyectos y fichas (nunca su contenido) y permisos. Desde ahí se hace o se deja de ser administrador (salvo uno mismo), se da o se quita el permiso de valorar se marca un email como verificado si no le llega el correo y se dan de alta cuentas ("Nueva cuenta": nombre, email, contraseña inicial generada y editable, y permisos; el email queda verificado y los datos se muestran una vez para copiarlos y pasárselos a la docente, que cambia la contraseña en su perfil; sin email de invitación mientras el correo de producción no esté configurado). Tests en `AdminCuentasTest`.
- **Extra: administración aparte de la app** (misma tabla `users` y misma sesión, pero zona separada). Entrada propia en `/admin/login`: solo deja entrar al administrador y a quien valora (que allí solo ve la batería); si hay sesión de docente, la sustituye. Sin sesión, `/admin/*` lleva ahí (`redirectGuestsTo`), y el middleware `admin` también manda ahí a una docente con sesión (antes daba 403). Layout `layouts.admin` con su menú oscuro (`layout.navegacion-admin`: Resumen, Configuración de la IA, Uso, Batería, Cuentas, "Ir a la app" y cerrar sesión, que vuelve a `/admin/login`). Portada `/admin` con recuentos de cuentas, contenido y la IA de 7 días. En el menú de la docente, el administrador solo ve "Administración". Tests en `AdminAccesoTest`.
- **Antes de empezar:** el proyecto ya está en git (`main` en GitHub) y se han borrado los `.tgz` de transferencia de `storage/app/private/`.
- Tests en `AdminIATest` y `ClienteIATest`.

## Objetivo
Que Nacho pueda **cambiar proveedor y modelo por tarea sin tocar la base de datos**, ver cuánto y cómo se usa la IA y, antes de cambiar de proveedor, **comparar resultados con casos reales** lado a lado.

## Antes de empezar
- Recoger los comentarios de la beta tester sobre proyectos y fichas (sprints 2 y 3). Definen los casos de la batería y qué es un "buen resultado".
- Poner `GEMINI_API_KEY` (y la que haga falta) en `.env` si se va a comparar con Gemini.

## A. Panel de configuración (`/admin/ia`, middleware `admin`)
- Una tarjeta por tarea (`proyecto`, `ajuste`, `ficha`, `prueba`, `imagen`) con sus filas de `ia_configuraciones`: proveedor, modelo (vacío = por defecto), orden (principal y respaldos), timeout y activo.
- Añadir proveedor a una tarea, reordenar (subir/bajar), editar modelo y timeout, activar o desactivar.
- **Validaciones:**
  - Cada tarea debe tener al menos un proveedor activo.
  - Avisar si el proveedor no tiene clave en la configuración del SDK.
  - En `imagen`, solo proveedores que generan imágenes (OpenAI, Gemini, xAI).
- **Botón "Probar"** por fila: llamada corta (como `ia:probar`) forzando ese proveedor y modelo. Necesita que `ClienteIA` acepte una configuración explícita en lugar de leerla de `ia_configuraciones`; mantener la firma actual para el resto de llamadas.
- Enlace "IA" en el menú solo para administradores.

## B. Uso y costes (`/admin/ia/uso`)
- Resumen por tarea y proveedor en un periodo (7 días, 30 días): generaciones, % fallidas, % respondidas por el respaldo, duración media, tokens de entrada, salida y caché, ids descartados.
- Últimas generaciones con su error si fallaron.
- **Coste estimado: pospuesto** (decisión de Nacho, 26 oct). Se puede añadir cuando se quiera: cada generación ya guarda proveedor, modelo y tokens, así que el coste se calculará también para el historial. Al hacerlo:
  - Tabla de precios por modelo con la **fecha desde la que vale** cada precio, para que el historial use el precio de su momento.
  - Hoy solo se guardan los tokens **leídos** de caché, no los **escritos** (Anthropic los cobra más caros). Sin ese dato el coste sale algo por debajo del real; guardarlo pronto da más historial exacto.

## C. Respaldo
- `ClienteIA` ya pasa la lista de proveedores al SDK. Añadir un test que confirme que, si el principal falla, responde el siguiente y `generaciones.respaldo = true`.

## D. Batería de pruebas comparativa
- **Casos** (`ia_casos`): nombre, tarea (`proyecto` o `ficha`) y entrada en JSON con lo que necesita el generador (curso, áreas, idea o tema, duración, alcance, alumnos con refuerzo y ampliación). Sin datos de alumnos. Seeder con los casos reales: "Exploradores del planeta", el proyecto de artes escénicas (alcance centro) y 3–4 fichas que elija la beta tester.
- **Ejecuciones** (`ia_ejecuciones`): caso, proveedor, modelo, estado, resultado JSON, duración, tokens, ids descartados, error, y valoración (1–5 y nota).
- **Lanzar:** comando `ia:bateria {--proveedores=anthropic,openai,gemini} {--caso=}` y botón en el panel. Cada ejecución es un job en cola.
- **Reutilizar los generadores sin guardar proyectos ni fichas reales.** `GeneradorProyecto::generar(Proyecto)` y `GeneradorFicha::generar(Ficha)` reciben modelos: probar a pasar instancias sin guardar (con la clase como relación en memoria). Si no es limpio, extraer la construcción del prompt y la limpieza a un método que reciba los datos de entrada. Decidir al empezar.
- **Vista de comparación** (`/admin/ia/pruebas/{caso}`): resultados lado a lado con los mismos componentes que ve la docente (`x-proyecto.documento`, `x-ficha.hoja`), duración, tokens e ids descartados. **Modo a ciegas**: proveedores ocultos como A, B, C hasta valorar.
- Las ejecuciones se registran en `generaciones` como cualquier otra llamada (tarea `prueba` o la del caso, por decidir).

## Tests previstos
- Solo el administrador entra en `/admin/ia` y sus subpáginas (docente → 403).
- No se puede dejar una tarea sin proveedor activo; reordenar cambia el principal que usa `ClienteIA`.
- "Probar" usa el proveedor de la fila.
- Respaldo cuando falla el principal.
- La batería crea una ejecución por proveedor y caso, guarda el resultado limpio (sin ids inventados) y no crea proyectos ni fichas.
- La valoración se guarda y el modo a ciegas no muestra el proveedor.

## Decidido (26 oct)
- Se compara con Claude, ChatGPT y Gemini (por defecto en `ia:bateria`).
- Valoran Nacho y la beta tester, nadie más: permiso `puede_valorar`, no rol de administrador.
- Coste en euros pospuesto (ver B).

## Pendiente de Nacho
- `GEMINI_API_KEY` en `.env` (local y servidor); sin ella, las ejecuciones con Gemini salen como fallidas.
- En el servidor: `php artisan migrate`, `php artisan db:seed --class=IaCasosSeeder` y `php artisan usuarios:valorar {email de la beta tester}`.
- Que la beta tester elija sus 3 o 4 fichas para sustituir las dos provisionales del seeder.

## Siguiente: sprint 5 (exportación Word y PDF, estilos visuales)

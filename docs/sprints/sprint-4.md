# Sprint 4: administración de IA y batería de pruebas comparativa

*Plan · Sin empezar · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 3](sprint-3.md)*

## Objetivo
Que Nacho pueda **cambiar proveedor y modelo por tarea sin tocar la base de datos**, ver cuánto y cómo se usa la IA y, antes de cambiar de proveedor, **comparar resultados con casos reales** lado a lado.

## Antes de empezar
- `git init` y primer commit (el proyecto no está en git).
- Borrar los archivos de transferencia de `storage/app/private/` (`snapshot*.tgz`, `sprint*.tgz`, `imagenes.tgz`).
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
- **Coste estimado** a partir de una tabla de precios por modelo mantenida a mano (p. ej. `config/ia-precios.php`), marcado como estimación.

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

## Preguntas abiertas
- ¿Con qué proveedores y modelos se compara de entrada? (Claude, ChatGPT y Gemini como mínimo).
- ¿La beta tester valora las comparaciones? Si sí, hace falta un acceso para ella (enlace firmado o permiso de solo valoración) sin darle el rol de administrador.
- ¿Precios por modelo a mano o se dejan los costes solo en tokens por ahora?

## Siguiente: sprint 5 (exportación Word y PDF, estilos visuales)

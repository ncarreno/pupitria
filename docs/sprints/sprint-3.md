# Sprint 3: fichas en 3 niveles

*Octubre 2026 · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 2](sprint-2.md) · **Hecho; tests en verde (PHP 8.4, Herd); en prueba con la beta tester***

## Hecho
- **Migración** `fichas` (clase, proyecto y tarea opcionales, área, curso, tema, título, `contenido` JSON con los 3 niveles, con_imagen, imagen, estado, error, ids descartados) y pivote `criterio_ficha`.
- **Modelo `Ficha`** con factory; `FichaPolicy` (solo la autora); `User::fichas()` y `Proyecto::fichas()`.
- **`GeneradorFicha`**:
  - Prompt portado del prototipo (`resources/views/prompts/ficha*.blade.php`).
  - Cada nivel empieza con una lectura con contenido real.
  - Un único esquema de bloque para los 8 tipos (lectura, preguntas, opciones, completar, tabla, verdadero_falso, relacionar, dibujo), para que funcione igual con cualquier proveedor.
  - Descarta tipos desconocidos y criterios que no son del área y ciclo.
- **Job `GenerarFicha`**: genera el texto con la IA de la tarea `ficha` y, si se pidió, la ilustración. Si falla la ilustración, la ficha se guarda sin imagen y avisa; si falla el texto, queda en error con "Volver a intentarlo".
- **Páginas:**
  - `/fichas`: listado propio.
  - `/fichas/nueva`: clase, área, qué trabajar e ilustración. Se puede abrir desde una tarea del proyecto (`?proyecto=…&tarea=…`) y rellena tema y área; nunca usa un proyecto ajeno.
  - `/fichas/{id}`: pestañas Refuerzo, Estándar y Ampliación; edición con un clic de todos los textos (no del tipo de bloque); **Imprimir** (solo imprime la hoja); generar de nuevo; borrar.
- **Componente `x-ficha.hoja`**: hoja imprimible con nombre y fecha; el nivel solo se ve como puntos (● ●● ●●●); letra más grande en refuerzo.
- **En la página del proyecto**: sección "Fichas de este proyecto", con "Crear ficha" por tarea y enlaces a las fichas creadas.
- **Menú**: añadido "Fichas".

### Añadido el 26 oct: ilustración con OpenAI o con prompt para ChatGPT o Gemini
Decisión de Nacho: la docente elige en cada ficha cómo se hace la ilustración (solo la imagen).
- **Migración** `2026_10_26_120000_add_imagen_por_to_fichas`: `imagen_por` (`openai` | `prompt`, por defecto `openai`) y `prompt_imagen`.
- **`/fichas/nueva`**: tres opciones (`Ficha::ILUSTRACIONES`): Sin ilustración, La crea la app, Prompt para ChatGPT o Gemini.
- **Job**: con ilustración guarda siempre el prompt (apaisado 3:2, estilo imprimible, `ClienteIA::SIN_TEXTO`). Solo llama a OpenAI si `imagen_por = openai`; si falla, el aviso invita a usar el prompt.
- **`/fichas/{id}`**: panel fuera de la hoja (no se imprime) con prompt editable, "Copiar el prompt", enlaces a ChatGPT y Gemini, y subir, cambiar o quitar la imagen (PNG, JPG o WebP, hasta 10 MB; borra la anterior). Visible en modo prompt, o en modo OpenAI si no hay imagen.

### Tests (`FichasTest`, 13)
Generación con limpieza, ilustración con OpenAI, fallo de la ilustración (conserva el prompt), modo prompt sin llamar a OpenAI, sin ilustración no hay prompt, subir/rechazar/quitar imagen, otra docente sin acceso, fallo del texto, rellenado desde una tarea (componente y enlace HTTP), proyecto ajeno, autoría y edición solo de textos permitidos. Pasan con el resto de la suite.

## Pendiente
- Prueba con la beta tester: fichas desde las tareas de "Exploradores del planeta"; valorar si los tres niveles se diferencian de verdad en papel; comparar ilustración con OpenAI y con prompt (calidad impresa y comodidad de copiar y subir). Sus comentarios pueden pedir ajustes en `resources/views/prompts/ficha*.blade.php`.

## Siguiente: [sprint 4](sprint-4.md) (panel de administración de IA y batería de pruebas comparativa)

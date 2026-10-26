# Sprint 2: generador de proyectos

*Octubre 2026 · MySQL en local con Herd · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 1](sprint-1.md) · **Hecho; en prueba con la beta tester***

## Hecho
- **Migración** `proyectos` (clase, tipo aula/centro, curso, áreas, idea, duración, `contenido` JSON, estado generando/listo/error, error, ids descartados), `proyecto_versiones` y pivotes `criterio_proyecto` y `proyecto_saber`.
- **Modelos** `Proyecto` (con `guardarVersion()`: guarda contenido, versión y pivotes) y `ProyectoVersion`; `ProyectoFactory`; `ProyectoPolicy` (solo la autora).
- **`GeneradorProyecto`**: prompt portado del prototipo como vistas Blade (`resources/views/prompts/`), currículo del ciclo al principio (para la caché), contexto de la clase (alumnos, refuerzo, ampliación), esquema JSON del Laravel AI SDK y limpieza: criterios y saberes fuera del ciclo o de las áreas, áreas de tarea, ODS fuera de 1–17 e itinerario (EVCE solo en 6.º).
- **Job `GenerarProyecto`** en cola: si falla la primera generación, queda en error con "Volver a intentarlo"; si falla un ajuste, se conserva el proyecto anterior.
- **Páginas:**
  - `/proyectos`: listado, solo los propios.
  - `/proyectos/nuevo`: clase, idea, áreas de esa clase, duración, alcance (aula o centro).
  - `/proyectos/{id}`: consulta el estado cada 3 s mientras genera; documento con su plantilla en estilo "cuaderno"; atajos y ajuste libre; edición a mano con un clic, solo en campos de texto (el currículo solo cambia pidiendo un ajuste); historial de versiones con "Restaurar"; borrar.
- **Componente `x-proyecto.documento`**: competencias, competencias clave y descriptores calculados, criterios resumidos y anexo con el texto literal. Fuentes desde Bunny Fonts (sin Google).
- **Menú**: Inicio, Mis clases, Proyectos, Currículo.
- **Tests (`ProyectosTest`, 9)**: generación con limpieza de ids inventados, clase ajena, solo la autora ve el proyecto, ajuste con versión nueva, fallo de la IA, edición solo de campos permitidos, intrusa sin acceso, listado propio y restaurar versión.

## Pendiente
- Prueba real con la beta tester: "Exploradores del planeta" y el proyecto de artes escénicas (alcance centro). Sus comentarios pueden pedir ajustes en los prompts de `resources/views/prompts/proyecto*.blade.php`.

## Siguiente: [sprint 3](sprint-3.md) (fichas en 3 niveles)

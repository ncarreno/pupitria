# Sprint 1: Mis clases

*Octubre 2026 · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 0](sprint-0.md) · **Cerrado** (la beta tester ya tiene su clase creada)*

## Hecho
- **Migración** `clases` (user_id, nombre, curso, alumnos, alumnos con refuerzo, alumnos con ampliación, curso escolar) y pivote `area_clase`. Sin datos personales de alumnos.
- **Modelo `Clase`** con factory: relaciones `user` y `areas`, `ciclo()` y `Clase::cursoEscolarActual()` (cambia en septiembre). `User::clases()`.
- **`ClasePolicy`**: cada docente solo ve, edita y borra sus clases.
- **Página `/clases`** (Volt), "Mis clases" en el menú:
  - Crear, editar y borrar (con confirmación en línea).
  - Solo muestra las áreas que existen en el curso elegido (Valores Cívicos y Éticos solo en 6.º); al cambiar de curso quita las que ya no valen.
  - Valida: al menos un área, entre 1 y 40 alumnos, refuerzo + ampliación ≤ total, curso escolar con formato 2026/2027.
  - Si la docente no tiene clases, el formulario aparece abierto.
- **Inicio (dashboard)** en español: invita a configurar la clase o muestra el resumen de clases.
- **Tests (`ClasesTest`, 10)**: acceso con sesión, creación con áreas, validaciones, EVCE solo en 6.º, aislamiento entre docentes, borrado, inicio y cambio de curso escolar.

## Siguiente: [sprint 2](sprint-2.md) (generador de proyectos)

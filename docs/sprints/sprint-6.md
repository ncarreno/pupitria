# Sprint 6: biblioteca, búsqueda, duplicar y control de uso

*Octubre 2026 · Diseño en [../arquitectura.md](../arquitectura.md) · Anterior: [sprint 5](sprint-5.md) · **Hecho (27 oct); 162 tests en verde***

## Objetivo
Que la docente **encuentre y reutilice** lo que ya ha hecho (buscar, filtrar y duplicar para otra clase o el curso siguiente) y que Nacho pueda **limitar cuánto genera cada cuenta** antes de abrir la app a más docentes.

## Hecho
- **A. Búsqueda y filtros** sobre las dos listas que ya existían (sin página "Biblioteca" nueva).
  - `/proyectos`: texto en título e idea, clase, curso, área y criterio. `/fichas`: texto en título y tema, curso, área, proyecto y criterio.
  - Filtros en la URL (`#[Url]`), paginación de 20 y estado vacío propio con "Quitar los filtros". Cada filtro solo aparece si tiene más de una opción; el de criterio ofrece los de sus documentos (`Criterio::etiqueta()`: `CN 5.1 · 2.º ciclo`).
  - Todo parte de `auth()->user()->proyectos()` / `fichas()`: nunca aparece nada de otra docente. Nuevas relaciones `Criterio::proyectos()` y `Criterio::fichas()`.
  - Tests en `BibliotecaTest` (6).
- **B. Duplicar** proyectos y fichas en una clase suya **del mismo curso** (los criterios son del ciclo y el contenido está escrito para ese curso). Componente `x-duplicar`, `Proyecto::duplicarEn()` y `Ficha::duplicarEn()`.
  - El proyecto copia contenido, estilo, criterios y saberes y empieza en la versión 1 ("Copia de «…»"); sus fichas no se copian.
  - La ficha copia contenido, criterios, estilo y prompt, y la ilustración **en un archivo nuevo** (borrar una no deja a la otra sin imagen). Mantiene el enlace a la tarea del proyecto.
  - El título lleva "(copia)" y se abre la copia. No llama a la IA.
  - Tests en `DuplicarTest` (4).
- **C. Control de uso.**
  - Límite mensual en `LIMITE_GENERACIONES_MES` (`config/uso.php`). **Vacío = sin límite**, que es como está ahora.
  - Cuentan las tareas `proyecto`, `ajuste` y `ficha` completadas en el mes natural (`User::TAREAS_CON_LIMITE`), más los proyectos y fichas que se están generando (su generación no se registra hasta que el job empieza; así no se cuelan varias seguidas). No cuentan las fallidas, la ilustración, las pruebas ni la batería.
  - **Sin límite:** el administrador siempre, y las cuentas con `users.sin_limite`, que el administrador marca en `/admin/cuentas` ("Quitar límite" / "Poner límite", y casilla en "Nueva cuenta"). `/admin/cuentas` muestra también las generaciones del mes de cada cuenta.
  - `User::limiteMensual()`, `generacionesEsteMes()`, `generacionesRestantes()`, `puedeGenerar()` y `mensajeLimite()`.
  - Se comprueba en las cinco acciones que generan (crear proyecto, ajustar, reintentar, crear ficha y generar de nuevo la ficha). La docente ve "Te quedan N generaciones de L este mes" junto a esos botones (componente `x-limite-uso`) y, al llegar, un aviso con la fecha en que se reinicia y los botones desactivados.
  - Tests en `LimiteUsoTest` (9).

## Decidido (27 oct)
- Búsqueda y filtros en las listas de siempre, sin página nueva.
- Duplicar solo a clases del mismo curso.
- El límite es igual para todas las cuentas, con excepciones marcadas por el administrador (la beta tester).

## Pendiente
- **Planes de pago:** el límite dependerá del plan de cada cuenta en lugar de ser uno para todas. Al hacerlo, `User::limiteMensual()` leerá el plan; el resto (recuento, avisos y bloqueo) no cambia.

## Pendiente de Nacho
- Decidir el número de `LIMITE_GENERACIONES_MES` cuando se abra la app a más docentes.
- Marcar "Quitar límite" a la beta tester en `/admin/cuentas`.
- En el servidor: `php artisan migrate` y `npm run build`.

## Siguiente
La hoja de ruta de la v1 está completa salvo el Word (sprint 5, a la espera de la beta tester). Después, lo de "Después de la v1" en [../arquitectura.md](../arquitectura.md#hoja-de-ruta-sprints-de-12-semanas).

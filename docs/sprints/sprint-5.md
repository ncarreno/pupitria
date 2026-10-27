# Sprint 5: estilos visuales e impresión en A4 (Word pospuesto)

*Octubre 2026 · Diseño en [../arquitectura.md](../arquitectura.md#exportación-sprint-5) · Anterior: [sprint 4](sprint-4.md) · **Hecho A y B (27 oct); 143 tests en verde. El Word (C) espera a la beta tester***

## Objetivo
Que la docente se lleve el proyecto y las fichas fuera de la app (un PDF que salga como lo ve en pantalla y, si lo usa, un Word editable) y que elija entre varios **estilos visuales** para que no salgan todos iguales.

## Hecho
- **A. Estilos visuales.** No se trajeron los del prototipo: se diseñaron seis propuestas nuevas (página de muestra con A4 y blanco y negro) y Nacho eligió cuatro: **Cuaderno** (el de antes, sin cambios), **Caligrafía**, **Cartulina** y **Esencial**.
  - Catálogo en `App\View\EstilosDocumento` (nombre, descripción y fuentes de Bunny Fonts de cada estilo). `resolver()`: el estilo del documento; si no tiene o ya no existe, el del perfil; si no, `cuaderno`.
  - CSS sacado de los componentes a `resources/css/documentos.css` (importado en `app.css`). La base es Cuaderno; cada estilo es la clase `t-{estilo}` en `x-ficha.hoja` y `x-proyecto.documento`, que cargan solo las fuentes de su estilo.
  - Cambio de marcado: cada bloque de la ficha lleva `b-{tipo}` (así la lectura no se numera como actividad) y las preguntas usan la clase `pregunta` en lugar de un `style`.
  - Migración `add_estilo_to_proyectos_y_fichas`: `estilo` nullable (null = el del perfil). `Ficha::estiloVisual()` y `Proyecto::estiloVisual()`.
  - Selector "Estilo" (`x-selector-estilo`) en `/fichas/{id}` y `/proyectos/{id}`; método `cambiarEstilo()` con la policy `update`. El perfil tiene "Estilo de tus documentos" (`profile.update-estilo-form`), que guarda `users.preferencias.estilo`.
  - La batería de pruebas compara siempre con Cuaderno (sus documentos no tienen docente).
  - Tests en `EstilosDocumentoTest` (10).
- **B. PDF desde el navegador.** Páginas de impresión con solo el documento en A4 (`ImpresionController`, componente `x-impresion`, vistas `impresion/`), que abren el diálogo de imprimir; desde ahí se guarda como PDF.
  - `GET /proyectos/{proyecto}/imprimir` y `GET /fichas/{ficha}/imprimir?nivel=` (un nivel, o `todos`: los tres, cada uno en su hoja). Middleware `can:view`. Lo que está en `generando` o sin contenido vuelve a su página.
  - Botones "Imprimir" (y "Los 3 niveles" en fichas) que abren la página en otra pestaña.
  - CSS de impresión al final de `documentos.css`: `@page` A4 con 12 mm, el proyecto a dos columnas, secciones sin partir y `print-color-adjust: exact` para que salgan pautas y colores. Las reglas de móvil son solo `screen`, porque una A4 impresa mide unos 720 px.
  - Tests en `ImpresionTest` (7).

## Decidido (27 oct)
- Los cuatro estilos de arriba.
- **No se instala nada en el servidor:** nada de Chromium. El PDF lo hace el navegador de la docente. Si hace falta un botón de descarga directa, `dompdf` (paquete de Composer) con una maqueta más sencilla, porque no entiende CSS grid.
- Las fuentes siguen en Bunny Fonts: al imprimir desde el navegador cargan bien, así que no hace falta tenerlas en local.
- El Word se pospone hasta saber si la beta tester lo usa.

## Pendiente
- **C. Word (.docx)**, si la beta tester lo usa. Plan:
  - `phpoffice/phpword` (paquete de Composer; necesita la aprobación de Nacho). Word no entiende el CSS, así que cada estilo tiene su versión para Word (fuentes, colores de títulos, bordes de tabla). Editable de verdad: encabezados con estilos de Word, tablas reales y listas.
  - Servicios `App\Services\Exportacion\WordProyecto` y `WordFicha`. Leen `contenido` con las mismas claves que los componentes y usan `CatalogoCurriculo::derivados()` para las competencias.
  - Ficha con los tres niveles, cada uno en su página: líneas con tabulación de puntos, casillas ○, banco de palabras en recuadro, tablas reales, recuadro para dibujar y la ilustración incrustada.
  - Rutas `GET /proyectos/{proyecto}/word` y `GET /fichas/{ficha}/word`, con la misma policy y la misma regla de estado que la impresión.
  - Por decidir: fuentes (las del estilo, que Word sustituirá si no están instaladas, o fuentes que tenga cualquier ordenador) y orden del proyecto (el de la pantalla o el formato del centro).

## Pendiente de Nacho
- Preguntar a la beta tester:
  - ¿Retoca en Word lo que saca o lo imprime tal cual? Decide si se hace C.
  - ¿El centro le pide la situación de aprendizaje en un formato concreto?
  - Que pruebe los estilos y la impresión (en A4, el proyecto a dos columnas deja huecos junto a las secciones largas; se puede reordenar si molesta).
- En el servidor: `php artisan migrate` y `npm run build`.

## Siguiente: [sprint 6](sprint-6.md) (biblioteca, búsqueda, duplicar, control de uso)

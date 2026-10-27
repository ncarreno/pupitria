# Sprint 5: exportación Word y PDF, estilos visuales

*En curso · Diseño en [../arquitectura.md](../arquitectura.md#exportación-sprint-5) · Anterior: [sprint 4](sprint-4.md)*

## Hecho hasta ahora (27 oct)
- **A. Estilos visuales.** No se trajeron los del prototipo: se diseñaron seis propuestas nuevas (página de muestra con A4 y blanco y negro) y Nacho eligió cuatro: **Cuaderno** (el de antes, sin cambios), **Caligrafía**, **Cartulina** y **Esencial**.
  - Catálogo en `App\View\EstilosDocumento` (nombre, descripción y fuentes de Bunny Fonts de cada estilo). `resolver()`: el estilo del documento; si no tiene o ya no existe, el del perfil; si no, `cuaderno`.
  - CSS sacado de los componentes a `resources/css/documentos.css` (importado en `app.css`). La base es Cuaderno; cada estilo es la clase `t-{estilo}` en `x-ficha.hoja` y `x-proyecto.documento`, que cargan solo las fuentes de su estilo.
  - Cambio de marcado: cada bloque de la ficha lleva `b-{tipo}` (así la lectura no se numera como actividad) y las preguntas usan la clase `pregunta` en lugar de un `style`.
  - Migración `add_estilo_to_proyectos_y_fichas`: `estilo` nullable (null = el del perfil). `Ficha::estiloVisual()` y `Proyecto::estiloVisual()`.
  - Selector "Estilo" (`x-selector-estilo`) en `/fichas/{id}` (junto a Imprimir) y `/proyectos/{id}`; método `cambiarEstilo()` con la policy `update`. El perfil tiene "Estilo de tus documentos" (`profile.update-estilo-form`), que guarda `users.preferencias.estilo`.
  - La batería de pruebas compara siempre con Cuaderno (sus documentos no tienen docente).
  - Tests en `EstilosDocumentoTest` (10).
- **Pendiente de A:** fuentes en local (`public/fonts`) para el PDF; de momento siguen en Bunny Fonts. En A4 el proyecto tendrá que pasar a dos columnas (la rejilla de 12 deja secciones de unos 150 px); se hará con el PDF.

## Objetivo
Que la docente se lleve el proyecto y las fichas fuera de la app: un **Word que pueda seguir editando** y un **PDF que salga como lo ve en pantalla**. Y que elija entre varios **estilos visuales** para que no salgan todos iguales.

## Punto de partida
- Hay **un solo estilo**: cuadrícula, Kalam y Atkinson Hyperlegible, azul y rojo. Su CSS está escrito dentro de `x-proyecto.documento` y `x-ficha.hoja` (`@once <style>`), y las fuentes vienen de fonts.bunny.net.
- Las fichas ya se imprimen desde el navegador (botón "Imprimir" en `/fichas/{id}`). Los proyectos no tienen nada para imprimir ni exportar.
- `users.preferencias` (JSON, cast `array`) existe desde el sprint 0 y no se usa.
- No están instalados ni PhpWord ni ningún paquete de PDF.

## Antes de empezar
- **Chromium en el servidor Plesk:** comprobar si se pueden instalar Chromium y Node. De eso depende el motor de PDF (ver B).
- **Aprobar las dependencias nuevas:** `phpoffice/phpword` y `spatie/laravel-pdf` (o la alternativa de B).
- **Preguntar a la beta tester:**
  - ¿Retoca en Word lo que saca o lo imprime tal cual?
  - ¿Imprime los tres niveles de una ficha a la vez?
  - ¿El centro le pide la situación de aprendizaje en un formato concreto? Decide cómo se ordena el proyecto en Word (ver C).

## A. Estilos visuales como temas
- **Sacar el CSS de los componentes a temas.** Un archivo por tema en `resources/css/temas/` con variables (colores, fuentes, fondo, bordes) y una base común que solo usa esas variables. El tema actual pasa a ser el primero sin que cambie su aspecto.
- **Catálogo de temas** como constante (clave => nombre), igual que `Ficha::NIVELES` o `Ficha::ILUSTRACIONES`. Dónde va se decide al empezar.
- **Estilo por defecto de la docente** en `users.preferencias.estilo`, elegido en su perfil.
- **Estilo de cada documento:** columna `estilo` nullable en `proyectos` y `fichas` (null = el de su preferencia). Selector en `/proyectos/{id}` y `/fichas/{id}` que cambia la vista en el momento. La batería de pruebas compara siempre con el tema por defecto.
- **Fuentes en local** (`public/fonts`) en lugar de fonts.bunny.net, para que el PDF no dependa de salir a internet.
- Que los 4 temas se lean bien impresos **en blanco y negro**, porque muchos colegios imprimen así.

## B. PDF
- **Motor recomendado:** `spatie/laravel-pdf` con Browsershot (Chromium). Usa el mismo HTML y CSS que la pantalla (grid, variables y fuentes), así que el PDF sale igual que en pantalla. Necesita Node y Chromium en el servidor.
- **Si Plesk no permite Chromium:** dompdf no soporta CSS grid y casi no soporta flex, así que habría que maquetar el PDF aparte de la pantalla. La otra opción es un Chromium remoto (por ejemplo Gotenberg en otro servidor de la UE). Se decide con el resultado de "Antes de empezar".
- **Rutas:**
  - `GET /proyectos/{proyecto}/pdf`
  - `GET /fichas/{ficha}/pdf?nivel=` con un nivel o los tres, cada uno en su página.
  - Las dos con la policy `view`. Solo se exporta lo que está terminado, no lo que está en `generando` o en `error`.
- **Vista:** los mismos componentes con `editable=false`, en A4 con márgenes. Los bloques no se parten entre páginas (`break-inside: avoid` ya está en la hoja). Cada hoja de ficha lleva nombre y fecha. La ilustración se lee del disco, no de una URL. El CSS del tema se incrusta con `Vite::content()`.
- **Botón "Descargar PDF"** junto a "Imprimir" en fichas, y nuevo en proyectos. El archivo se nombra con el título en slug.
- Para empezar se genera en la propia petición (1–3 s). Si tarda más en el servidor, pasa a la cola.

## C. Word (.docx)
- **`phpoffice/phpword`.** Word no entiende el CSS, así que cada tema tiene también su versión para Word (fuentes, colores de títulos, bordes de tabla). No sale idéntico al PDF, pero sí editable de verdad: encabezados con estilos de Word, tablas reales y listas.
- **Servicios** `App\Services\Exportacion\WordProyecto` y `WordFicha`, que reciben el modelo y el tema. Leen `contenido` igual que los componentes (mismas claves) y usan `CatalogoCurriculo::derivados()` para las competencias, como el documento en pantalla.
- **Proyecto:** el mismo orden que en pantalla, o el formato del centro si la beta tester tiene uno.
- **Ficha:** los 8 tipos de bloque.
  - Líneas para escribir: tabulación con puntos.
  - Opciones: casillas ○.
  - Completar: el banco de palabras en recuadro.
  - Tabla y relacionar: tablas reales.
  - Dibujo: un recuadro vacío.
  - La ilustración va incrustada. Los tres niveles van en el mismo archivo, cada uno con un salto de página.
- **Fuentes:** Kalam no estará instalada en el ordenador de la docente y Word la cambiará por otra. Hay dos opciones: usar en Word fuentes que tenga cualquier ordenador o aceptar la sustitución. Por decidir.
- **Rutas** `GET /proyectos/{proyecto}/word` y `GET /fichas/{ficha}/word`, con la misma policy y la misma regla de estado que el PDF.

## Tests previstos
- **Aislamiento:** otra docente no descarga ni el PDF ni el Word de un proyecto o una ficha ajenos (403). Sin sesión lleva a `login`.
- **Estado:** un proyecto o una ficha en `generando` o en `error` no se exporta.
- **PDF:** con `Pdf::fake()` de `spatie/laravel-pdf`, sin Chromium en los tests. Comprueban la vista, el nivel pedido (o los tres) y el tema aplicado.
- **Word:** se genera un `.docx` válido que contiene el título, los criterios y los bloques de cada nivel (se lee con PhpWord o se abre `word/document.xml`).
- **Temas:**
  - Sin estilo propio, el documento usa la preferencia de la docente, y sin preferencia, el tema por defecto.
  - El estilo del documento manda sobre la preferencia.
  - Un tema que no existe se rechaza.

## Por decidir
- Motor de PDF, según lo que permita el servidor.
- En el PDF de una ficha: ¿por defecto un nivel o los tres?
- Fuentes en Word.
- Orden del proyecto en Word: el de la pantalla o el formato del centro.

## Siguiente: sprint 6 (biblioteca, búsqueda, duplicar, control de uso)

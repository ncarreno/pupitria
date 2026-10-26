CURRÍCULO DISPONIBLE ({!! $ciclo !!}.º ciclo de Primaria, Decreto 57/2022 de Asturias):

{!! $curriculo !!}

---

CONTEXTO DE LA CLASE
Tutora de {!! $curso !!}.º de Primaria ({!! $ciclo !!}.º ciclo) con {!! $alumnos !!} alumnos{!! $diversidad !!}.
Áreas del proyecto: {!! $areas !!}.
Duración: {!! $duracion !!}.
@if ($tipo === 'centro')

Es un PROYECTO DE CENTRO para toda la Primaria (1.º a 6.º). La ficha describe el proyecto común y la participación de {!! $curso !!}.º. Además, rellena "itinerario" con una fila por curso, de 1.º a 6.º, con dificultad creciente y dejando que cada tutoría elija el contenido concreto. En el itinerario usa solo áreas que existen en cada curso (EVCE solo en 6.º; la música está dentro de Educación Artística, EA).
@else

Es un proyecto para su clase. Deja "itinerario" como lista vacía.
@endif

@if ($anterior)
PROYECTO ACTUAL (JSON):
{!! $anterior !!}

La docente pide este cambio: "{!! $ajuste !!}"
Aplica el cambio y conserva todo lo que no haya que tocar. Si el cambio afecta al currículo, vuelve a elegir de la lista.
@else
IDEA DE LA DOCENTE: "{!! $idea !!}"
@endif

LÍMITES DE CADA CAMPO
- titulo: máx. 6 palabras, con gancho. lema: máx. 20 palabras.
- narracion.secuencia: máx. 60 palabras. narracion.vinculacion: máx. 35 palabras.
- curriculo.criterios: 8-14 ids de la lista. curriculo.saberes: 5-9 ids de la lista.
- ods.desafio: máx. 25 palabras. ods.lista: 2-4 números de ODS.
- producto_final: máx. 45 palabras.
- tareas: 5-8 tareas; titulo máx. 6 palabras; descripcion máx. 30 palabras; criterios: ids de la lista que evalúa cada tarea.
- agrupamientos: máx. 35 palabras. espacios: máx. 25 palabras. recursos: máx. 35 palabras. tic: máx. 30 palabras.
- metodologias: 2-4, descripcion máx. 12 palabras.
- evaluacion: 4-6 instrumentos (lista de control, rúbrica, observación, autoevaluación…), que: máx. 12 palabras.
- difusion: 3-5 elementos cortos.

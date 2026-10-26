CRITERIOS DE EVALUACIÓN DISPONIBLES ({!! $area !!}, {!! $ciclo !!}.º ciclo; elige 1-3 ids, SOLO de esta lista):
{!! $criterios !!}

---

CONTEXTO
Ficha para {!! $curso !!}.º de Primaria, área de {!! $nombreArea !!}.
@if ($proyecto)
Forma parte del proyecto "{!! $proyecto !!}".
@endif
@if ($tarea)
Tarea del proyecto: {!! $tarea !!}
@endif
Lo que pide la docente: "{!! $tema !!}"

FORMATO DE LOS BLOQUES
Todos los bloques tienen todos los campos; deja vacíos ("" o []) los que no use su tipo.
- lectura: titulo + texto (60-120 palabras en estándar; más corto en refuerzo).
- preguntas: enunciado + preguntas + lineas (líneas para responder, 1-4).
- opciones: enunciado + items (cada uno con pregunta y 3 opciones).
- completar: enunciado + texto con ___ en cada hueco + banco (palabras que faltan).
- tabla: enunciado + columnas + filas (cada fila es una lista de celdas; las celdas "" quedan en blanco para rellenar).
- verdadero_falso: enunciado + frases.
- relacionar: enunciado + izquierda + derecha (la derecha desordenada respecto a la izquierda).
- dibujo: enunciado.

ILUSTRACIÓN
En "ilustracion" describe en una frase una ilustración sencilla que acompañe a la ficha (qué se ve, sin textos). Si no tiene sentido una ilustración, déjalo vacío.

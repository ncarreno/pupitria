<?php

namespace App\Services\Proyectos;

use App\Models\Area;
use App\Models\Generacion;
use App\Models\Proyecto;
use App\Services\Curriculo\CatalogoCurriculo;
use App\Services\IA\ClienteIA;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Genera o ajusta el documento "Diseño de un proyecto" con la IA configurada.
 * La IA solo puede elegir criterios y saberes por id; los que no existen se descartan.
 */
class GeneradorProyecto
{
    public function __construct(
        private ClienteIA $ia,
        private CatalogoCurriculo $catalogo,
    ) {}

    /**
     * @return array{contenido: array<string, mixed>, descartados: int, generacion: Generacion}
     */
    public function generar(Proyecto $proyecto, ?string $ajuste = null): array
    {
        $ciclo = CatalogoCurriculo::cicloDe($proyecto->curso);
        $clase = $proyecto->clase;

        $diversidad = $clase
            ? " ({$clase->alumnos_refuerzo} necesitan refuerzo y {$clase->alumnos_ampliacion} ampliación)"
            : '';

        $peticion = view('prompts.proyecto', [
            'curriculo' => $this->catalogo->paraPrompt($proyecto->areas, $ciclo),
            'ciclo' => $ciclo,
            'curso' => $proyecto->curso,
            'alumnos' => $clase?->alumnos ?? 20,
            'diversidad' => $diversidad,
            'areas' => Area::whereIn('codigo', $proyecto->areas)->orderBy('orden')->get()
                ->map(fn (Area $a) => "{$a->codigo} ({$a->nombre_corto})")->implode(', '),
            'duracion' => $proyecto->duracion,
            'tipo' => $proyecto->tipo,
            'idea' => $proyecto->idea,
            'anterior' => $ajuste ? json_encode($proyecto->contenido, JSON_UNESCAPED_UNICODE) : null,
            'ajuste' => $ajuste,
        ])->render();

        $resultado = $this->ia->generarEstructurado(
            tarea: $ajuste ? 'ajuste' : 'proyecto',
            instrucciones: view('prompts.proyecto-instrucciones')->render(),
            peticion: $peticion,
            esquema: fn (JsonSchema $schema) => $this->esquema($schema),
            usuario: $proyecto->user,
        );

        [$contenido, $descartados] = $this->limpiar($resultado['datos'], $proyecto, $ciclo);
        $resultado['generacion']->update(['ids_descartados' => $descartados]);

        return ['contenido' => $contenido, 'descartados' => $descartados, 'generacion' => $resultado['generacion']];
    }

    /**
     * @return array<string, mixed>
     */
    private function esquema(JsonSchema $s): array
    {
        $textos = fn () => $s->array()->items($s->string());

        return [
            'titulo' => $s->string()->required(),
            'lema' => $s->string()->required(),
            'temporalizacion' => $s->string()->required(),
            'narracion' => $s->object([
                'secuencia' => $s->string()->required(),
                'vinculacion' => $s->string()->required(),
            ])->required(),
            'curriculo' => $s->object([
                'criterios' => $textos()->required(),
                'saberes' => $textos()->required(),
            ])->required(),
            'ods' => $s->object([
                'desafio' => $s->string()->required(),
                'lista' => $s->array()->items($s->integer())->required(),
            ])->required(),
            'producto_final' => $s->string()->required(),
            'tareas' => $s->array()->items($s->object([
                'titulo' => $s->string()->required(),
                'descripcion' => $s->string()->required(),
                'areas' => $textos()->required(),
                'criterios' => $textos()->required(),
            ]))->required(),
            'agrupamientos' => $s->string()->required(),
            'espacios' => $s->string()->required(),
            'metodologias' => $s->array()->items($s->object([
                'nombre' => $s->string()->required(),
                'descripcion' => $s->string()->required(),
            ]))->required(),
            'recursos' => $s->string()->required(),
            'tic' => $s->string()->required(),
            'evaluacion' => $s->array()->items($s->object([
                'instrumento' => $s->string()->required(),
                'que' => $s->string()->required(),
            ]))->required(),
            'difusion' => $textos()->required(),
            'itinerario' => $s->array()->items($s->object([
                'curso' => $s->integer()->required(),
                'formato' => $s->string()->required(),
                'elige_profesorado' => $s->string()->required(),
                'aporta_alumnado' => $s->string()->required(),
                'reto' => $s->string()->required(),
                'areas' => $textos()->required(),
                'duracion' => $s->string()->required(),
            ]))->required(),
        ];
    }

    /**
     * Normaliza la respuesta de la IA y descarta criterios, saberes, áreas y ODS que no existen.
     *
     * @param  array<string, mixed>  $raw
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function limpiar(array $raw, Proyecto $proyecto, int $ciclo): array
    {
        $areas = $proyecto->areas;
        $texto = fn ($v): string => trim((string) (is_scalar($v) ? $v : ''));
        $lista = fn ($v): array => is_array($v) ? array_values($v) : [];

        [$criterios, $malCriterios] = $this->catalogo->filtrarCriterios($lista(data_get($raw, 'curriculo.criterios')), $ciclo, $areas);
        [$saberes, $malSaberes] = $this->catalogo->filtrarSaberes($lista(data_get($raw, 'curriculo.saberes')), $ciclo, $areas);
        $descartados = $malCriterios + $malSaberes;

        $tareas = collect($lista($raw['tareas'] ?? []))->map(function ($t) use ($texto, $lista, $areas, $ciclo, &$descartados) {
            [$ids, $mal] = $this->catalogo->filtrarCriterios($lista($t['criterios'] ?? []), $ciclo, $areas);
            $descartados += $mal;

            return [
                'titulo' => $texto($t['titulo'] ?? ''),
                'descripcion' => $texto($t['descripcion'] ?? ''),
                'areas' => array_values(array_intersect($lista($t['areas'] ?? []), $areas)),
                'criterios' => $ids,
            ];
        })->all();

        $contenido = [
            'titulo' => $texto($raw['titulo'] ?? '') ?: 'Proyecto sin título',
            'lema' => $texto($raw['lema'] ?? ''),
            'temporalizacion' => $texto($raw['temporalizacion'] ?? $proyecto->duracion),
            'narracion' => [
                'secuencia' => $texto(data_get($raw, 'narracion.secuencia')),
                'vinculacion' => $texto(data_get($raw, 'narracion.vinculacion')),
            ],
            'curriculo' => ['criterios' => $criterios, 'saberes' => $saberes],
            'ods' => [
                'desafio' => $texto(data_get($raw, 'ods.desafio')),
                'lista' => collect($lista(data_get($raw, 'ods.lista')))->map(fn ($n) => (int) $n)
                    ->filter(fn (int $n) => $n >= 1 && $n <= 17)->unique()->take(4)->values()->all(),
            ],
            'producto_final' => $texto($raw['producto_final'] ?? ''),
            'tareas' => $tareas,
            'agrupamientos' => $texto($raw['agrupamientos'] ?? ''),
            'espacios' => $texto($raw['espacios'] ?? ''),
            'metodologias' => collect($lista($raw['metodologias'] ?? []))
                ->map(fn ($m) => ['nombre' => $texto($m['nombre'] ?? ''), 'descripcion' => $texto($m['descripcion'] ?? '')])->all(),
            'recursos' => $texto($raw['recursos'] ?? ''),
            'tic' => $texto($raw['tic'] ?? ''),
            'evaluacion' => collect($lista($raw['evaluacion'] ?? []))
                ->map(fn ($e) => ['instrumento' => $texto($e['instrumento'] ?? ''), 'que' => $texto($e['que'] ?? '')])->all(),
            'difusion' => collect($lista($raw['difusion'] ?? []))->map($texto)->filter()->values()->all(),
            'itinerario' => [],
        ];

        if ($proyecto->tipo === 'centro') {
            $porCurso = Area::all()->mapWithKeys(fn (Area $a) => [$a->codigo => $a->cursos]);

            $contenido['itinerario'] = collect($lista($raw['itinerario'] ?? []))
                ->map(fn ($r) => [
                    'curso' => (int) ($r['curso'] ?? 0),
                    'formato' => $texto($r['formato'] ?? ''),
                    'elige_profesorado' => $texto($r['elige_profesorado'] ?? ''),
                    'aporta_alumnado' => $texto($r['aporta_alumnado'] ?? ''),
                    'reto' => $texto($r['reto'] ?? ''),
                    'areas' => array_values(array_filter(
                        $lista($r['areas'] ?? []),
                        fn ($a) => in_array((int) ($r['curso'] ?? 0), $porCurso[$a] ?? [], true),
                    )),
                    'duracion' => $texto($r['duracion'] ?? ''),
                ])
                ->filter(fn (array $r) => $r['curso'] >= 1 && $r['curso'] <= 6)
                ->sortBy('curso')->values()->all();
        }

        return [$contenido, $descartados];
    }
}

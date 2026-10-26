<?php

namespace App\Services\Fichas;

use App\Models\Area;
use App\Models\Criterio;
use App\Models\Ficha;
use App\Models\Generacion;
use App\Models\IaConfiguracion;
use App\Services\Curriculo\CatalogoCurriculo;
use App\Services\IA\ClienteIA;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Genera una ficha en tres niveles con la IA de texto configurada.
 * Los criterios se validan contra el decreto y los bloques se normalizan a los tipos conocidos.
 */
class GeneradorFicha
{
    public const TIPOS = ['lectura', 'preguntas', 'opciones', 'completar', 'tabla', 'verdadero_falso', 'relacionar', 'dibujo'];

    public function __construct(
        private ClienteIA $ia,
        private CatalogoCurriculo $catalogo,
    ) {}

    /**
     * @param  IaConfiguracion|null  $soloEsta  para la batería de pruebas: fuerza ese proveedor y registra la llamada como `bateria`
     * @return array{contenido: array<string, mixed>, titulo: string, criterios: array<string>, ilustracion: string, descartados: int, generacion: Generacion}
     */
    public function generar(Ficha $ficha, ?IaConfiguracion $soloEsta = null): array
    {
        $ciclo = CatalogoCurriculo::cicloDe($ficha->curso);
        $area = Area::where('codigo', $ficha->area)->firstOrFail();
        $tarea = $ficha->proyecto && $ficha->tarea_indice !== null
            ? data_get($ficha->proyecto->contenido, "tareas.{$ficha->tarea_indice}")
            : null;

        $peticion = view('prompts.ficha', [
            'area' => $area->codigo,
            'nombreArea' => $area->nombre,
            'ciclo' => $ciclo,
            'curso' => $ficha->curso,
            'criterios' => Criterio::where('area_id', $area->id)->where('ciclo', $ciclo)->orderBy('id')->get()
                ->map(fn (Criterio $c) => "{$c->id}: {$c->texto}")->implode("\n"),
            'proyecto' => $ficha->proyecto?->titulo,
            'tarea' => $tarea ? "{$tarea['titulo']}: {$tarea['descripcion']}" : null,
            'tema' => $ficha->tema,
        ])->render();

        $resultado = $this->ia->generarEstructurado(
            tarea: $soloEsta ? 'bateria' : 'ficha',
            instrucciones: view('prompts.ficha-instrucciones')->render(),
            peticion: $peticion,
            esquema: fn (JsonSchema $schema) => $this->esquema($schema),
            usuario: $ficha->user,
            soloEsta: $soloEsta,
        );

        $raw = $resultado['datos'];
        [$criterios, $descartados] = $this->catalogo->filtrarCriterios(
            is_array($raw['criterios'] ?? null) ? $raw['criterios'] : [], $ciclo, [$area->codigo]
        );

        $contenido = [];
        foreach (array_keys(Ficha::NIVELES) as $nivel) {
            $contenido[$nivel] = [
                'consigna' => $this->texto(data_get($raw, "niveles.{$nivel}.consigna")),
                'bloques' => $this->bloques(data_get($raw, "niveles.{$nivel}.bloques")),
            ];
        }

        $resultado['generacion']->update(['ids_descartados' => $descartados]);

        return [
            'contenido' => $contenido,
            'titulo' => str($this->texto($raw['titulo'] ?? ''))->limit(255, '')->toString() ?: 'Ficha',
            'criterios' => $criterios,
            'ilustracion' => $this->texto($raw['ilustracion'] ?? ''),
            'descartados' => $descartados,
            'generacion' => $resultado['generacion'],
        ];
    }

    /**
     * Mismo esquema de bloque para todos los tipos: así funciona igual con cualquier proveedor.
     *
     * @return array<string, mixed>
     */
    private function esquema(JsonSchema $s): array
    {
        $textos = fn () => $s->array()->items($s->string());

        $bloque = $s->object([
            'tipo' => $s->string()->enum(self::TIPOS)->required(),
            'titulo' => $s->string()->required(),
            'enunciado' => $s->string()->required(),
            'texto' => $s->string()->required(),
            'preguntas' => $textos()->required(),
            'lineas' => $s->integer()->required(),
            'items' => $s->array()->items($s->object([
                'pregunta' => $s->string()->required(),
                'opciones' => $textos()->required(),
            ]))->required(),
            'frases' => $textos()->required(),
            'banco' => $textos()->required(),
            'columnas' => $textos()->required(),
            'filas' => $s->array()->items($textos())->required(),
            'izquierda' => $textos()->required(),
            'derecha' => $textos()->required(),
        ]);

        $nivel = fn () => $s->object([
            'consigna' => $s->string()->required(),
            'bloques' => $s->array()->items($bloque)->required(),
        ])->required();

        return [
            'titulo' => $s->string()->required(),
            'criterios' => $textos()->required(),
            'ilustracion' => $s->string()->required(),
            'niveles' => $s->object([
                'refuerzo' => $nivel(),
                'estandar' => $nivel(),
                'ampliacion' => $nivel(),
            ])->required(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bloques(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->filter(fn ($b) => is_array($b) && in_array($b['tipo'] ?? null, self::TIPOS, true))
            ->map(fn (array $b) => [
                'tipo' => $b['tipo'],
                'titulo' => $this->texto($b['titulo'] ?? ''),
                'enunciado' => $this->texto($b['enunciado'] ?? ''),
                'texto' => $this->texto($b['texto'] ?? ''),
                'preguntas' => $this->textos($b['preguntas'] ?? []),
                'lineas' => max(1, min(4, (int) ($b['lineas'] ?? 2))),
                'items' => collect(is_array($b['items'] ?? null) ? $b['items'] : [])
                    ->map(fn ($i) => ['pregunta' => $this->texto($i['pregunta'] ?? ''), 'opciones' => $this->textos($i['opciones'] ?? [])])
                    ->values()->all(),
                'frases' => $this->textos($b['frases'] ?? []),
                'banco' => $this->textos($b['banco'] ?? []),
                'columnas' => $this->textos($b['columnas'] ?? []),
                'filas' => collect(is_array($b['filas'] ?? null) ? $b['filas'] : [])
                    ->map(fn ($f) => collect(is_array($f) ? $f : [])->map(fn ($c) => $this->texto($c))->values()->all())
                    ->values()->all(),
                'izquierda' => $this->textos($b['izquierda'] ?? []),
                'derecha' => $this->textos($b['derecha'] ?? []),
            ])
            ->values()->all();
    }

    private function texto(mixed $valor): string
    {
        return trim(is_scalar($valor) ? (string) $valor : '');
    }

    /**
     * @return array<int, string>
     */
    private function textos(mixed $valores): array
    {
        return collect(is_array($valores) ? $valores : [])->map(fn ($v) => $this->texto($v))->filter()->values()->all();
    }
}

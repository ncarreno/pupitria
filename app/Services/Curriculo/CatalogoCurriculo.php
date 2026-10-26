<?php

namespace App\Services\Curriculo;

use App\Models\Area;
use App\Models\Criterio;
use App\Models\Saber;
use Illuminate\Support\Collection;

/**
 * Acceso al currículo para la generación con IA:
 * - prepara la lista de criterios y saberes que se le da a la IA;
 * - valida que los ids que devuelve la IA existen en el decreto;
 * - calcula competencias y descriptores a partir de los criterios (no se piden a la IA).
 */
class CatalogoCurriculo
{
    public static function cicloDe(int $curso): int
    {
        return (int) ceil($curso / 2);
    }

    /**
     * Bloque de texto con el currículo del ciclo y las áreas, para el prompt.
     * Va siempre al principio del prompt para aprovechar la caché de los proveedores.
     *
     * @param  array<string>  $codigosArea
     */
    public function paraPrompt(array $codigosArea, int $ciclo, int $maxSaber = 170): string
    {
        return Area::whereIn('codigo', $codigosArea)
            ->with([
                'criterios' => fn ($q) => $q->where('ciclo', $ciclo)->orderBy('id'),
                'saberes' => fn ($q) => $q->where('ciclo', $ciclo),
            ])
            ->orderBy('orden')
            ->get()
            ->map(function (Area $area) use ($ciclo, $maxSaber) {
                $criterios = $area->criterios->map(fn (Criterio $c) => "{$c->id}: {$c->texto}")->implode("\n");
                $saberes = $area->saberes->map(fn (Saber $s) => "{$s->id}: ".str($s->texto)->limit($maxSaber))->implode("\n");

                return "### {$area->codigo} — {$area->nombre}\n"
                    ."Criterios de evaluación ({$ciclo}.º ciclo):\n{$criterios}\n"
                    ."Saberes básicos ({$ciclo}.º ciclo):\n{$saberes}";
            })
            ->implode("\n\n");
    }

    /**
     * Devuelve solo los ids de criterio válidos para el ciclo y las áreas, y cuántos se descartaron.
     *
     * @param  array<mixed>  $ids
     * @param  array<string>  $codigosArea
     * @return array{0: array<string>, 1: int}
     */
    public function filtrarCriterios(array $ids, int $ciclo, array $codigosArea): array
    {
        return $this->filtrar(Criterio::class, $ids, $ciclo, $codigosArea);
    }

    /**
     * @param  array<mixed>  $ids
     * @param  array<string>  $codigosArea
     * @return array{0: array<string>, 1: int}
     */
    public function filtrarSaberes(array $ids, int $ciclo, array $codigosArea): array
    {
        return $this->filtrar(Saber::class, $ids, $ciclo, $codigosArea);
    }

    /**
     * Competencias específicas, competencias clave y descriptores que se derivan de unos criterios.
     *
     * @param  array<string>  $idsCriterio
     * @return array{competencias: Collection, descriptores: Collection, competencias_clave: Collection}
     */
    public function derivados(array $idsCriterio): array
    {
        $competencias = Criterio::whereIn('id', $idsCriterio)
            ->with('competencia.descriptores', 'competencia.area')
            ->get()
            ->pluck('competencia')
            ->unique('id')
            ->values();

        $descriptores = $competencias->flatMap->descriptores->unique('id')->sortBy('codigo')->values();

        return [
            'competencias' => $competencias,
            'descriptores' => $descriptores,
            'competencias_clave' => $descriptores->pluck('competencia_clave_nombre', 'competencia_clave')->unique(),
        ];
    }

    /**
     * @param  class-string<Criterio|Saber>  $modelo
     * @param  array<mixed>  $ids
     * @param  array<string>  $codigosArea
     * @return array{0: array<string>, 1: int}
     */
    private function filtrar(string $modelo, array $ids, int $ciclo, array $codigosArea): array
    {
        $pedidos = collect($ids)->filter(fn ($id) => is_string($id))->unique()->values();

        $validos = $modelo::query()
            ->whereIn('id', $pedidos)
            ->where('ciclo', $ciclo)
            ->whereHas('area', fn ($q) => $q->whereIn('codigo', $codigosArea))
            ->pluck('id');

        $ordenados = $pedidos->filter(fn ($id) => $validos->contains($id))->values()->all();

        return [$ordenados, count($ids) - count($ordenados)];
    }
}

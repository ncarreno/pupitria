<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['id', 'competencia_especifica_id', 'area_id', 'ciclo', 'codigo', 'texto'])]
class Criterio extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public function competencia(): BelongsTo
    {
        return $this->belongsTo(CompetenciaEspecifica::class, 'competencia_especifica_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class);
    }

    public function fichas(): BelongsToMany
    {
        return $this->belongsToMany(Ficha::class);
    }

    /**
     * Código corto para filtros y listas, como `CN 2.1 · 2.º ciclo` (el mismo código se repite en cada ciclo).
     */
    public function etiqueta(): string
    {
        return "{$this->area->codigo} {$this->codigo} · {$this->ciclo}.º ciclo";
    }
}

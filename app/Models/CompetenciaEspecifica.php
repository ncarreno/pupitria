<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['area_id', 'codigo', 'numero', 'enunciado'])]
class CompetenciaEspecifica extends Model
{
    protected $table = 'competencias_especificas';

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function criterios(): HasMany
    {
        return $this->hasMany(Criterio::class)->orderBy('codigo');
    }

    public function descriptores(): BelongsToMany
    {
        return $this->belongsToMany(Descriptor::class, 'competencia_descriptor');
    }
}

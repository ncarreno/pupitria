<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['normativa_id', 'codigo', 'nombre', 'nombre_corto', 'cursos', 'orden'])]
class Area extends Model
{
    protected function casts(): array
    {
        return ['cursos' => 'array'];
    }

    public function normativa(): BelongsTo
    {
        return $this->belongsTo(Normativa::class);
    }

    public function competencias(): HasMany
    {
        return $this->hasMany(CompetenciaEspecifica::class)->orderBy('numero');
    }

    public function criterios(): HasMany
    {
        return $this->hasMany(Criterio::class);
    }

    public function saberes(): HasMany
    {
        return $this->hasMany(Saber::class)->orderBy('orden');
    }

    public function existeEnCurso(int $curso): bool
    {
        return in_array($curso, $this->cursos ?? [], true);
    }
}

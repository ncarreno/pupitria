<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valoración de una ejecución por una persona (de 1 a 5, con nota opcional).
 */
#[Fillable(['user_id', 'puntuacion', 'nota'])]
class IaValoracion extends Model
{
    protected $table = 'ia_valoraciones';

    protected function casts(): array
    {
        return ['puntuacion' => 'integer'];
    }

    public function ejecucion(): BelongsTo
    {
        return $this->belongsTo(IaEjecucion::class, 'ia_ejecucion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'tarea', 'estado', 'proveedor', 'modelo', 'respaldo',
    'tokens_entrada', 'tokens_salida', 'tokens_cache', 'duracion_ms', 'ids_descartados', 'error',
])]
class Generacion extends Model
{
    protected $table = 'generaciones';

    protected function casts(): array
    {
        return ['respaldo' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

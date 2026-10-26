<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['numero', 'contenido', 'instruccion', 'proveedor', 'modelo'])]
class ProyectoVersion extends Model
{
    protected $table = 'proyecto_versiones';

    protected function casts(): array
    {
        return ['contenido' => 'array'];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tarea', 'proveedor', 'modelo', 'orden', 'timeout', 'activo'])]
class IaConfiguracion extends Model
{
    protected $table = 'ia_configuraciones';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function scopeParaTarea(Builder $query, string $tarea): Builder
    {
        return $query->where('tarea', $tarea)->where('activo', true)->orderBy('orden');
    }
}

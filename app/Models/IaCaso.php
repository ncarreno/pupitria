<?php

namespace App\Models;

use Database\Factories\IaCasoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Caso real de la batería de pruebas. `entrada` lleva lo que necesita el generador
 * (curso, áreas, idea o tema, duración, alcance, cuántos alumnos), nunca datos de alumnos.
 */
#[Fillable(['nombre', 'tarea', 'entrada'])]
class IaCaso extends Model
{
    /** @use HasFactory<IaCasoFactory> */
    use HasFactory;

    public const TAREAS = ['proyecto' => 'Proyecto', 'ficha' => 'Ficha'];

    protected $table = 'ia_casos';

    protected function casts(): array
    {
        return ['entrada' => 'array'];
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(IaEjecucion::class)->latest('id');
    }

    /**
     * La última ejecución de cada proveedor y modelo: es lo que se compara.
     *
     * @return Collection<int, IaEjecucion>
     */
    public function ultimasEjecuciones(): Collection
    {
        return $this->ejecuciones()->with('generacion', 'valoraciones.user')->get()
            ->unique(fn (IaEjecucion $e) => $e->proveedor.'|'.$e->modelo)
            ->values();
    }
}

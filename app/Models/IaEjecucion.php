<?php

namespace App\Models;

use Database\Factories\IaEjecucionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una pasada de un caso con un proveedor y modelo. La duración, los tokens y los ids
 * descartados están en su `generacion`.
 */
#[Fillable(['proveedor', 'modelo', 'estado', 'resultado', 'error', 'generacion_id'])]
class IaEjecucion extends Model
{
    /** @use HasFactory<IaEjecucionFactory> */
    use HasFactory;

    protected $table = 'ia_ejecuciones';

    protected function casts(): array
    {
        return ['resultado' => 'array'];
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(IaCaso::class, 'ia_caso_id');
    }

    public function generacion(): BelongsTo
    {
        return $this->belongsTo(Generacion::class);
    }

    public function valoraciones(): HasMany
    {
        return $this->hasMany(IaValoracion::class);
    }

    public function valoracionDe(User $usuario): ?IaValoracion
    {
        return $this->valoraciones->firstWhere('user_id', $usuario->id);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}

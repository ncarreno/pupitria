<?php

namespace App\Models;

use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['clase_id', 'tipo', 'curso', 'areas', 'titulo', 'idea', 'duracion', 'contenido', 'estado', 'error', 'ids_descartados'])]
class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'curso' => 'integer',
            'areas' => 'array',
            'contenido' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(ProyectoVersion::class)->orderByDesc('numero');
    }

    public function fichas(): HasMany
    {
        return $this->hasMany(Ficha::class)->latest('updated_at');
    }

    public function criterios(): BelongsToMany
    {
        return $this->belongsToMany(Criterio::class);
    }

    public function saberes(): BelongsToMany
    {
        return $this->belongsToMany(Saber::class);
    }

    public function estaGenerando(): bool
    {
        return $this->estado === 'generando';
    }

    /**
     * Guarda un contenido nuevo, lo registra como versión y sincroniza criterios y saberes.
     *
     * @param  array<string, mixed>  $contenido
     */
    public function guardarVersion(array $contenido, ?string $instruccion = null, ?string $proveedor = null, ?string $modelo = null): ProyectoVersion
    {
        $this->forceFill([
            'contenido' => $contenido,
            'titulo' => (string) ($contenido['titulo'] ?? $this->titulo),
            'estado' => 'listo',
            'error' => null,
        ])->save();

        $this->criterios()->sync($contenido['curriculo']['criterios'] ?? []);
        $this->saberes()->sync($contenido['curriculo']['saberes'] ?? []);

        return $this->versiones()->create([
            'numero' => (int) $this->versiones()->max('numero') + 1,
            'contenido' => $contenido,
            'instruccion' => $instruccion,
            'proveedor' => $proveedor,
            'modelo' => $modelo,
        ]);
    }
}

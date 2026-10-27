<?php

namespace App\Models;

use App\View\EstilosDocumento;
use Database\Factories\FichaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['clase_id', 'proyecto_id', 'tarea_indice', 'area', 'curso', 'tema', 'titulo', 'contenido', 'con_imagen', 'imagen_por', 'imagen', 'prompt_imagen', 'estado', 'error', 'ids_descartados'])]
class Ficha extends Model
{
    /** @use HasFactory<FichaFactory> */
    use HasFactory;

    public const NIVELES = ['refuerzo' => 'Refuerzo', 'estandar' => 'Estándar', 'ampliacion' => 'Ampliación'];

    protected $table = 'fichas';

    /**
     * Al borrar la ficha se borra también su ilustración del disco.
     */
    protected static function booted(): void
    {
        static::deleted(function (Ficha $ficha) {
            if ($ficha->imagen) {
                Storage::disk('public')->delete($ficha->imagen);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'curso' => 'integer',
            'tarea_indice' => 'integer',
            'contenido' => 'array',
            'con_imagen' => 'boolean',
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

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function criterios(): BelongsToMany
    {
        return $this->belongsToMany(Criterio::class);
    }

    public function estaGenerando(): bool
    {
        return $this->estado === 'generando';
    }

    /**
     * Opciones de ilustración que ve la docente al crear la ficha.
     */
    public const ILUSTRACIONES = [
        'ninguna' => 'Sin ilustración',
        'openai' => 'La crea la app',
        'prompt' => 'Prompt para ChatGPT o Gemini',
    ];

    public function ilustracionPorPrompt(): bool
    {
        return $this->con_imagen && $this->imagen_por === 'prompt';
    }

    public function urlImagen(): ?string
    {
        return $this->imagen ? Storage::disk('public')->url($this->imagen) : null;
    }

    /**
     * El estilo con el que se ve e imprime: el suyo o, si no tiene, el del perfil de la docente.
     */
    public function estiloVisual(): string
    {
        return EstilosDocumento::resolver($this->estilo, $this->user);
    }
}

<?php

namespace App\Models;

use App\Services\Curriculo\CatalogoCurriculo;
use Database\Factories\ClaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nombre', 'curso', 'alumnos', 'alumnos_refuerzo', 'alumnos_ampliacion', 'curso_escolar'])]
class Clase extends Model
{
    /** @use HasFactory<ClaseFactory> */
    use HasFactory;

    protected $table = 'clases';

    protected function casts(): array
    {
        return [
            'curso' => 'integer',
            'alumnos' => 'integer',
            'alumnos_refuerzo' => 'integer',
            'alumnos_ampliacion' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)->orderBy('orden');
    }

    public function ciclo(): int
    {
        return CatalogoCurriculo::cicloDe($this->curso);
    }

    /**
     * Curso escolar en curso: a partir de septiembre empieza el siguiente.
     */
    public static function cursoEscolarActual(): string
    {
        $anio = (int) now()->format('Y');
        $inicio = (int) now()->format('n') >= 9 ? $anio : $anio - 1;

        return $inicio.'/'.($inicio + 1);
    }
}

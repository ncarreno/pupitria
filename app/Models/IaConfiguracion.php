<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tarea', 'proveedor', 'modelo', 'orden', 'timeout', 'activo'])]
class IaConfiguracion extends Model
{
    protected $table = 'ia_configuraciones';

    /**
     * Tareas de la app que llaman a la IA, con el nombre que ve el administrador.
     */
    public const TAREAS = [
        'proyecto' => 'Proyectos',
        'ajuste' => 'Ajustes de proyectos',
        'ficha' => 'Fichas',
        'imagen' => 'Ilustraciones',
        'prueba' => 'Pruebas (ia:probar y botón Probar)',
    ];

    public const PROVEEDORES_TEXTO = [
        'anthropic' => 'Anthropic (Claude)',
        'openai' => 'OpenAI (ChatGPT)',
        'gemini' => 'Google (Gemini)',
        'mistral' => 'Mistral',
        'deepseek' => 'DeepSeek',
        'xai' => 'xAI (Grok)',
    ];

    /**
     * Solo estos generan imágenes en el Laravel AI SDK (Claude no).
     */
    public const PROVEEDORES_IMAGEN = [
        'openai' => 'OpenAI',
        'gemini' => 'Google (Gemini)',
        'xai' => 'xAI (Grok)',
    ];

    /**
     * Segundos máximos por tarea. Tienen que caber en el timeout del job: GenerarProyecto (300 s)
     * y GenerarFicha (400 s para el texto y la ilustración juntos: 240 + 150).
     */
    public const TIMEOUT_MAXIMO = ['proyecto' => 280, 'ajuste' => 280, 'ficha' => 240, 'imagen' => 150, 'prueba' => 120];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function scopeParaTarea(Builder $query, string $tarea): Builder
    {
        return $query->where('tarea', $tarea)->where('activo', true)->orderBy('orden');
    }

    /**
     * @return array<string, string>
     */
    public static function proveedoresPara(string $tarea): array
    {
        return $tarea === 'imagen' ? self::PROVEEDORES_IMAGEN : self::PROVEEDORES_TEXTO;
    }

    /**
     * Nombre de una tarea del registro de generaciones: las configurables y la batería de pruebas.
     */
    public static function nombreTarea(string $tarea): string
    {
        return (self::TAREAS + ['bateria' => 'Batería de pruebas'])[$tarea] ?? $tarea;
    }

    public static function timeoutMaximo(string $tarea): int
    {
        return self::TIMEOUT_MAXIMO[$tarea] ?? 120;
    }

    /**
     * Si el SDK tiene clave para el proveedor (en `.env`). Sin clave, las llamadas fallan.
     */
    public function tieneClave(): bool
    {
        return filled(config("ai.providers.{$this->proveedor}.key"));
    }
}

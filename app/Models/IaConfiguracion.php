<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Ai;
use Throwable;

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

    /**
     * Modelos que se ofrecen en el panel para un proveedor, sacados del Laravel AI SDK para que
     * sigan al día al actualizarlo: el más barato, el de por defecto y el más potente (texto)
     * o el de por defecto (imágenes). Se puede escribir cualquier otro a mano.
     *
     * @return array<string, string> [modelo => etiqueta]
     */
    public static function modelosPara(string $tarea, string $proveedor): array
    {
        try {
            if ($tarea === 'imagen') {
                return [Ai::imageProvider($proveedor)->defaultImageModel() => 'por defecto'];
            }

            $sdk = Ai::textProvider($proveedor);
            $etiquetas = [];
            $etiquetas[$sdk->cheapestTextModel()][] = 'económico';
            $etiquetas[$sdk->defaultTextModel()][] = 'por defecto';
            $etiquetas[$sdk->smartestTextModel()][] = 'el más potente';
        } catch (Throwable) {
            return [];
        }

        return array_map(fn (array $e) => implode(', ', $e), $etiquetas);
    }

    /**
     * El modelo que se usa con el campo vacío.
     */
    public static function modeloPorDefecto(string $tarea, string $proveedor): ?string
    {
        return collect(self::modelosPara($tarea, $proveedor))->search(fn (string $e) => str_contains($e, 'por defecto')) ?: null;
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

<?php

namespace Database\Seeders;

use App\Models\IaConfiguracion;
use Illuminate\Database\Seeder;

/**
 * Configuración inicial: Claude para el texto y OpenAI para las imágenes (Claude no genera imágenes).
 * El modelo vacío usa el modelo por defecto del proveedor en el Laravel AI SDK.
 * Se cambia luego desde el panel de administración.
 */
class IaConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['proyecto' => 240, 'ajuste' => 180, 'ficha' => 180, 'prueba' => 60] as $tarea => $timeout) {
            IaConfiguracion::firstOrCreate(
                ['tarea' => $tarea, 'proveedor' => 'anthropic'],
                ['modelo' => null, 'orden' => 1, 'timeout' => $timeout, 'activo' => true],
            );
        }

        IaConfiguracion::firstOrCreate(
            ['tarea' => 'imagen', 'proveedor' => 'openai'],
            ['modelo' => null, 'orden' => 1, 'timeout' => 120, 'activo' => true],
        );
    }
}

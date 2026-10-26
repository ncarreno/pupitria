<?php

namespace Database\Factories;

use App\Models\IaCaso;
use App\Models\IaEjecucion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IaEjecucion>
 */
class IaEjecucionFactory extends Factory
{
    /**
     * Ejecución todavía en marcha.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ia_caso_id' => IaCaso::factory(),
            'proveedor' => 'anthropic',
            'modelo' => null,
            'estado' => 'pendiente',
        ];
    }
}

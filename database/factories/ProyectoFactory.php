<?php

namespace Database\Factories;

use App\Models\Clase;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clase_id' => Clase::factory()->state(['curso' => 3]),
            'user_id' => fn (array $atributos) => Clase::find($atributos['clase_id'])->user_id,
            'tipo' => 'aula',
            'curso' => 3,
            'areas' => ['LCL', 'CN'],
            'titulo' => 'Exploradores del planeta',
            'idea' => 'Un viaje por los continentes',
            'duracion' => '6 semanas',
            'contenido' => null,
            'estado' => 'generando',
        ];
    }
}

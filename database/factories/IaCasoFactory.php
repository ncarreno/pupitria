<?php

namespace Database\Factories;

use App\Models\IaCaso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IaCaso>
 */
class IaCasoFactory extends Factory
{
    /**
     * Caso de proyecto de aula de 3.º.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Exploradores del planeta',
            'tarea' => 'proyecto',
            'entrada' => [
                'curso' => 3, 'areas' => ['LCL', 'CN'], 'idea' => 'Un viaje por los continentes', 'duracion' => '6 semanas',
                'tipo' => 'aula', 'alumnos' => 20, 'alumnos_refuerzo' => 3, 'alumnos_ampliacion' => 2,
            ],
        ];
    }

    /**
     * Caso de ficha de Ciencias de la Naturaleza de 3.º.
     */
    public function ficha(): static
    {
        return $this->state([
            'nombre' => 'El koala',
            'tarea' => 'ficha',
            'entrada' => ['curso' => 3, 'area' => 'CN', 'tema' => 'Animales de Oceanía y cómo se adaptan a su ecosistema'],
        ]);
    }
}

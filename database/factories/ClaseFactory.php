<?php

namespace Database\Factories;

use App\Models\Clase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clase>
 */
class ClaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $curso = fake()->numberBetween(1, 6);

        return [
            'user_id' => User::factory(),
            'nombre' => $curso.'.º '.fake()->randomElement(['A', 'B', 'C']),
            'curso' => $curso,
            'alumnos' => 20,
            'alumnos_refuerzo' => 3,
            'alumnos_ampliacion' => 2,
            'curso_escolar' => Clase::cursoEscolarActual(),
        ];
    }
}

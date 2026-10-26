<?php

namespace Database\Factories;

use App\Models\Clase;
use App\Models\Ficha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ficha>
 */
class FichaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clase_id' => Clase::factory()->state(['curso' => 3]),
            'user_id' => fn (array $atributos) => Clase::find($atributos['clase_id'])->user_id,
            'area' => 'CN',
            'curso' => 3,
            'tema' => 'Animales de Oceanía y cómo se adaptan',
            'titulo' => 'El koala',
            'contenido' => null,
            'estado' => 'generando',
        ];
    }
}

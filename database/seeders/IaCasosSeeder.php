<?php

namespace Database\Seeders;

use App\Models\IaCaso;
use Illuminate\Database\Seeder;

/**
 * Casos de la batería de pruebas. Los proyectos son los que está probando la beta tester; las
 * fichas son provisionales hasta que ella elija las suyas. Sin datos de alumnos.
 */
class IaCasosSeeder extends Seeder
{
    public function run(): void
    {
        $casos = [
            'Exploradores del planeta' => ['proyecto', [
                'curso' => 3, 'areas' => ['LCL', 'CN', 'CS'], 'tipo' => 'aula', 'duracion' => '6 semanas',
                'idea' => 'Un viaje por los continentes: cada semana la clase llega a uno nuevo, conoce sus paisajes y animales y lo cuenta en su cuaderno de expedición.',
                'alumnos' => 20, 'alumnos_refuerzo' => 3, 'alumnos_ampliacion' => 2,
            ]],
            'Artes escénicas (centro)' => ['proyecto', [
                'curso' => 3, 'areas' => ['EA', 'LCL', 'EF'], 'tipo' => 'centro', 'duracion' => 'Un trimestre',
                'idea' => 'Proyecto de centro de artes escénicas: cada curso prepara una parte de un espectáculo para el festival de fin de curso.',
                'alumnos' => 20, 'alumnos_refuerzo' => 3, 'alumnos_ampliacion' => 2,
            ]],
            'Ficha: animales de Oceanía (provisional)' => ['ficha', [
                'curso' => 3, 'area' => 'CN', 'tema' => 'Animales de Oceanía y cómo se adaptan a su ecosistema. Con una lectura corta y nada de casillas vacías.',
            ]],
            'Ficha: leyenda del cuélebre (provisional)' => ['ficha', [
                'curso' => 3, 'area' => 'LCL', 'tema' => 'Comprensión lectora con una versión corta de la leyenda asturiana del cuélebre.',
            ]],
        ];

        foreach ($casos as $nombre => [$tarea, $entrada]) {
            IaCaso::firstOrCreate(['nombre' => $nombre], ['tarea' => $tarea, 'entrada' => $entrada]);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\CompetenciaEspecifica;
use App\Models\Criterio;
use App\Models\Descriptor;
use App\Models\Normativa;
use App\Models\Saber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa el currículo extraído del decreto (JSON) a las tablas del currículo.
 * Es idempotente: se puede ejecutar varias veces y actualiza lo que haya cambiado.
 */
#[Signature('curriculo:importar {archivo? : Ruta al JSON del currículo}')]
#[Description('Importa el currículo de Primaria de Asturias (Decreto 57/2022) desde el JSON')]
class ImportarCurriculo extends Command
{
    private const NOMBRES_CORTOS = [
        'CN' => 'Ciencias de la Naturaleza',
        'CS' => 'Ciencias Sociales',
        'EA' => 'Educación Artística',
        'EF' => 'Educación Física',
        'EVCE' => 'Valores Cívicos y Éticos',
        'LCL' => 'Lengua Castellana',
        'LE' => 'Lengua Extranjera',
        'MAT' => 'Matemáticas',
        'LAL' => 'Lengua Asturiana',
        'CA' => 'Cultura Asturiana',
    ];

    public function handle(): int
    {
        $ruta = $this->argument('archivo') ?? database_path('data/curriculo-primaria-asturias.json');

        if (! is_file($ruta)) {
            $this->error("No encuentro el archivo: {$ruta}");

            return self::FAILURE;
        }

        $datos = json_decode(file_get_contents($ruta), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($datos) {
            $normativa = Normativa::updateOrCreate(
                ['codigo' => 'asturias-primaria-d57-2022'],
                [
                    'comunidad' => $datos['comunidad'],
                    'etapa' => $datos['etapa'],
                    'referencia' => 'Decreto 57/2022, de 5 de agosto',
                    'fuente' => 'BOPA núm. 156, 12-VIII-2022',
                    'activa' => true,
                ],
            );

            foreach ($datos['perfil_salida']['competencias_clave'] as $cc) {
                foreach ($cc['descriptores'] as $d) {
                    Descriptor::updateOrCreate(
                        ['normativa_id' => $normativa->id, 'codigo' => $d['codigo']],
                        ['competencia_clave' => $cc['codigo'], 'competencia_clave_nombre' => $cc['nombre'], 'texto' => $d['texto']],
                    );
                }
            }
            $descriptores = Descriptor::where('normativa_id', $normativa->id)->pluck('id', 'codigo');

            foreach ($datos['areas'] as $orden => $a) {
                $area = Area::updateOrCreate(
                    ['normativa_id' => $normativa->id, 'codigo' => $a['codigo']],
                    [
                        'nombre' => $a['nombre'],
                        'nombre_corto' => self::NOMBRES_CORTOS[$a['codigo']] ?? $a['nombre'],
                        'cursos' => $a['codigo'] === 'EVCE' ? [6] : [1, 2, 3, 4, 5, 6],
                        'orden' => $orden,
                    ],
                );

                foreach ($a['competencias'] as $c) {
                    $competencia = CompetenciaEspecifica::updateOrCreate(
                        ['codigo' => $c['id']],
                        ['area_id' => $area->id, 'numero' => $c['numero'], 'enunciado' => $c['enunciado']],
                    );

                    $competencia->descriptores()->sync(
                        collect($c['descriptores'])->map(fn ($cod) => $descriptores[$cod] ?? null)->filter()->values()
                    );

                    foreach ($c['criterios'] as $cr) {
                        Criterio::updateOrCreate(
                            ['id' => $cr['id']],
                            [
                                'competencia_especifica_id' => $competencia->id,
                                'area_id' => $area->id,
                                'ciclo' => $cr['ciclo'],
                                'codigo' => $cr['codigo'],
                                'texto' => $cr['texto'],
                            ],
                        );
                    }
                }

                $n = 0;
                foreach ($a['saberes'] as $bloque) {
                    foreach ($bloque['apartados'] as $apartado) {
                        foreach ($apartado['items'] as $item) {
                            Saber::updateOrCreate(
                                ['id' => $item['id']],
                                [
                                    'area_id' => $area->id,
                                    'ciclo' => $bloque['ciclo'],
                                    'bloque' => $bloque['bloque'].'. '.$bloque['nombre'],
                                    'apartado' => $apartado['nombre'] ?: null,
                                    'texto' => $item['texto'],
                                    'orden' => $n++,
                                ],
                            );
                        }
                    }
                }
            }
        });

        $this->table(['Áreas', 'Competencias', 'Criterios', 'Saberes', 'Descriptores'], [[
            Area::count(), CompetenciaEspecifica::count(), Criterio::count(), Saber::count(), Descriptor::count(),
        ]]);
        $this->info('Currículo importado.');

        return self::SUCCESS;
    }
}

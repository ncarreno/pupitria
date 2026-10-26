<?php

namespace App\Jobs;

use App\Models\Proyecto;
use App\Services\Proyectos\GeneradorProyecto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Genera (o ajusta, si hay instrucción) un proyecto en segundo plano.
 * La página del proyecto consulta el estado hasta que deja de estar "generando".
 */
class GenerarProyecto implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public Proyecto $proyecto,
        public ?string $ajuste = null,
    ) {}

    public function handle(GeneradorProyecto $generador): void
    {
        try {
            $resultado = $generador->generar($this->proyecto, $this->ajuste);
        } catch (Throwable $e) {
            $this->failed($e);

            return;
        }

        $this->proyecto->forceFill(['ids_descartados' => $resultado['descartados']]);
        $this->proyecto->guardarVersion(
            $resultado['contenido'],
            $this->ajuste,
            $resultado['generacion']->proveedor,
            $resultado['generacion']->modelo,
        );
    }

    /**
     * Si falla un ajuste se conserva el proyecto anterior; si falla la primera generación queda en error.
     */
    public function failed(?Throwable $exception): void
    {
        $this->proyecto->forceFill([
            'estado' => $this->proyecto->contenido ? 'listo' : 'error',
            'error' => $this->ajuste
                ? 'No se ha podido aplicar el cambio. El proyecto sigue como estaba; vuelve a intentarlo.'
                : 'No se ha podido generar el proyecto. Vuelve a intentarlo en unos minutos.',
        ])->save();

        report($exception);
    }
}

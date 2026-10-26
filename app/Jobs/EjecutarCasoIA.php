<?php

namespace App\Jobs;

use App\Models\IaEjecucion;
use App\Services\IA\BateriaPruebas;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Pasa un caso de la batería por un proveedor. Si falla, la ejecución queda como fallida con su error.
 */
class EjecutarCasoIA implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public IaEjecucion $ejecucion) {}

    public function handle(BateriaPruebas $bateria): void
    {
        try {
            $bateria->ejecutar($this->ejecucion);
        } catch (Throwable $e) {
            $this->failed($e);
        }
    }

    /**
     * También la llama la cola si el job se corta (timeout o worker caído).
     */
    public function failed(?Throwable $exception): void
    {
        $this->ejecucion->forceFill([
            'estado' => 'fallida',
            'error' => $exception ? str($exception->getMessage())->limit(500)->toString() : 'El trabajo se cortó.',
        ])->save();

        if ($exception) {
            report($exception);
        }
    }
}

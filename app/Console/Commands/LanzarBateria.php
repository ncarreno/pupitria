<?php

namespace App\Console\Commands;

use App\Models\IaCaso;
use App\Services\IA\BateriaPruebas;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('ia:bateria {--proveedores=anthropic,openai,gemini : Proveedores separados por comas; admite proveedor:modelo} {--caso= : Id de un solo caso}')]
#[Description('Lanza la batería de pruebas comparativa: cada caso con cada proveedor (gasta saldo de API)')]
class LanzarBateria extends Command
{
    public function handle(BateriaPruebas $bateria): int
    {
        $casos = IaCaso::query()->when($this->option('caso'), fn ($q, $id) => $q->whereKey($id))->get();

        if ($casos->isEmpty()) {
            $this->error('No hay casos. Créalos con php artisan db:seed --class=IaCasosSeeder.');

            return self::FAILURE;
        }

        $proveedores = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('proveedores')))));

        try {
            foreach ($casos as $caso) {
                $ejecuciones = $bateria->lanzar($caso, $proveedores);
                $this->line("{$caso->nombre}: {$ejecuciones->count()} ejecuciones en cola.");
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Lanzado. Los resultados aparecen en /admin/ia/pruebas cuando la cola los procesa.');

        return self::SUCCESS;
    }
}

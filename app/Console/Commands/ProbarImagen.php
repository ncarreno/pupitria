<?php

namespace App\Console\Commands;

use App\Services\IA\ClienteIA;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('ia:probar-imagen')]
#[Description('Genera una ilustración de prueba con el proveedor de imágenes configurado (OpenAI)')]
class ProbarImagen extends Command
{
    public function handle(ClienteIA $ia): int
    {
        $this->info('Generando una ilustración de prueba…');

        try {
            $r = $ia->generarImagen('Un koala en una rama de eucalipto, ilustración sencilla para una ficha de 3.º de Primaria, colores suaves.');
        } catch (Throwable $e) {
            $this->error('Ha fallado: '.$e->getMessage());

            return self::FAILURE;
        }

        $g = $r['generacion'];
        $this->line("Guardada en storage/app/public/{$r['ruta']}");
        $this->line("URL: {$r['url']}");
        $this->line("Proveedor: {$g->proveedor} · Modelo: {$g->modelo} · {$g->duracion_ms} ms");

        return self::SUCCESS;
    }
}

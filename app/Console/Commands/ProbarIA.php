<?php

namespace App\Console\Commands;

use App\Services\IA\ClienteIA;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Throwable;

#[Signature('ia:probar {tarea=prueba : Tarea cuya configuración se usa}')]
#[Description('Hace una llamada corta a la IA configurada para comprobar claves, proveedor y respuesta estructurada')]
class ProbarIA extends Command
{
    public function handle(ClienteIA $ia): int
    {
        $this->info('Llamando a la IA…');

        try {
            $r = $ia->generarEstructurado(
                tarea: $this->argument('tarea'),
                instrucciones: 'Eres una herramienta para maestras de Primaria en Asturias. Responde en español de España.',
                peticion: 'Propón un título corto para un proyecto de 3.º de Primaria sobre los ríos de Asturias y una frase que lo explique.',
                esquema: fn (JsonSchema $schema) => [
                    'titulo' => $schema->string()->required(),
                    'lema' => $schema->string()->required(),
                ],
            );
        } catch (Throwable $e) {
            $this->error('Ha fallado: '.$e->getMessage());

            return self::FAILURE;
        }

        $g = $r['generacion'];
        $this->table(['Título', 'Lema'], [[$r['datos']['titulo'] ?? '—', $r['datos']['lema'] ?? '—']]);
        $this->line("Proveedor: {$g->proveedor} · Modelo: {$g->modelo} · Tokens: {$g->tokens_entrada} / {$g->tokens_salida} · {$g->duracion_ms} ms".($g->respaldo ? ' · (respaldo)' : ''));

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Models\Ficha;
use App\Services\Fichas\GeneradorFicha;
use App\Services\IA\ClienteIA;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Genera la ficha en tres niveles y, si se pidió, su ilustración: con OpenAI o como prompt
 * para que la docente lo copie en ChatGPT o Gemini. El prompt se guarda siempre, así que
 * si falla OpenAI la ficha queda sin imagen pero con el prompt a mano.
 */
class GenerarFicha implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 400;

    public function __construct(public Ficha $ficha) {}

    public function handle(GeneradorFicha $generador, ClienteIA $ia): void
    {
        try {
            $resultado = $generador->generar($this->ficha);
        } catch (Throwable $e) {
            $this->failed($e);

            return;
        }

        $error = null;
        $anterior = $this->ficha->imagen;
        $imagen = $anterior;
        $prompt = null;

        if ($this->ficha->con_imagen) {
            // La docente pidió ilustración: si la IA no propone ninguna, se ilustra el título.
            $descripcion = $resultado['ilustracion'] !== '' ? $resultado['ilustracion'] : 'Una escena que ilustre «'.$resultado['titulo'].'»';
            $estilo = $descripcion.'. Ilustración sencilla para una ficha escolar de Primaria que se va a imprimir: línea clara, colores planos y fondo blanco.';
            $prompt = 'Crea una imagen horizontal (apaisada, proporción 3:2). '.$estilo.' '.ClienteIA::SIN_TEXTO;

            if ($this->ficha->imagen_por === 'openai') {
                try {
                    $imagen = $ia->generarImagen($estilo, 'landscape', $this->ficha->user)['ruta'];
                } catch (Throwable $e) {
                    $error = 'La ficha está lista, pero no se ha podido crear la ilustración. Puedes copiar el prompt en ChatGPT o Gemini y subir la imagen.';
                    report($e);
                }
            }
        }

        $this->ficha->forceFill([
            'titulo' => $resultado['titulo'],
            'contenido' => $resultado['contenido'],
            'imagen' => $imagen,
            'prompt_imagen' => $prompt,
            'ids_descartados' => $resultado['descartados'],
            'estado' => 'listo',
            'error' => $error,
        ])->save();

        $this->ficha->criterios()->sync($resultado['criterios']);

        if ($anterior && $anterior !== $imagen) {
            Storage::disk('public')->delete($anterior);
        }
    }

    /**
     * También la llama la cola si el job se corta (timeout o worker caído), para que la ficha no se quede en "generando".
     */
    public function failed(?Throwable $exception): void
    {
        $this->ficha->forceFill([
            'estado' => 'error',
            'error' => 'No se ha podido generar la ficha. Vuelve a intentarlo en unos minutos.',
        ])->save();

        report($exception);
    }
}

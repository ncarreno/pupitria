<?php

namespace App\Services\IA;

use App\Models\Generacion;
use App\Models\IaConfiguracion;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

use function Laravel\Ai\agent;

/**
 * Punto único de llamada a la IA. No sabe qué proveedor hay debajo: lo lee de
 * `ia_configuraciones` (lo elige solo el administrador) y usa el respaldo del
 * Laravel AI SDK si el proveedor principal falla o limita la tasa.
 *
 * Cada llamada queda registrada en `generaciones` sin guardar el texto de la petición.
 *
 * Las imágenes usan su propia tarea (`imagen`), configurada con OpenAI, porque Claude no genera imágenes.
 */
class ClienteIA
{
    /**
     * Pide a la IA una respuesta estructurada (JSON con esquema).
     *
     * @param  string  $tarea  proyecto, ajuste, ficha…
     * @param  Closure  $esquema  fn (JsonSchema $schema): array — esquema del Laravel AI SDK
     * @return array{datos: array, generacion: Generacion}
     */
    public function generarEstructurado(
        string $tarea,
        string $instrucciones,
        string $peticion,
        Closure $esquema,
        ?User $usuario = null,
    ): array {
        $configuracion = IaConfiguracion::paraTarea($tarea)->get();

        if ($configuracion->isEmpty()) {
            throw new RuntimeException("No hay ningún proveedor de IA activo para la tarea «{$tarea}».");
        }

        // [proveedor => modelo|null], en orden: el primero es el principal, el resto son respaldo.
        $proveedores = $configuracion->mapWithKeys(fn (IaConfiguracion $c) => [$c->proveedor => $c->modelo])->all();
        $principal = $configuracion->first()->proveedor;

        $generacion = Generacion::create([
            'user_id' => $usuario?->id,
            'tarea' => $tarea,
            'estado' => 'pendiente',
        ]);

        $inicio = hrtime(true);

        try {
            /** @var StructuredAgentResponse $respuesta */
            $respuesta = agent(instructions: $instrucciones, schema: $esquema)
                ->prompt($peticion, provider: $proveedores, timeout: $configuracion->max('timeout'));
        } catch (Throwable $e) {
            $generacion->update([
                'estado' => 'fallida',
                'duracion_ms' => $this->ms($inicio),
                'error' => str($e::class.': '.$e->getMessage())->limit(1000),
            ]);

            throw $e;
        }

        $generacion->update([
            'estado' => 'completada',
            'proveedor' => $respuesta->meta->provider,
            'modelo' => $respuesta->meta->model,
            'respaldo' => $respuesta->meta->provider !== null && $respuesta->meta->provider !== $principal,
            'tokens_entrada' => $respuesta->usage->inputTokens,
            'tokens_salida' => $respuesta->usage->outputTokens,
            'tokens_cache' => $respuesta->usage->cacheReadInputTokens,
            'duracion_ms' => $this->ms($inicio),
        ]);

        return ['datos' => $respuesta->toArray(), 'generacion' => $generacion];
    }

    /**
     * Genera una ilustración y la guarda en el disco público.
     * Nunca se le pide texto dentro de la imagen: los textos van siempre en HTML editable.
     *
     * @param  'square'|'portrait'|'landscape'  $formato
     * @return array{ruta: string, url: string, generacion: Generacion}
     */
    public function generarImagen(
        string $descripcion,
        string $formato = 'square',
        ?User $usuario = null,
        string $tarea = 'imagen',
    ): array {
        $configuracion = IaConfiguracion::paraTarea($tarea)->get();

        if ($configuracion->isEmpty()) {
            throw new RuntimeException("No hay ningún proveedor de imágenes activo para la tarea «{$tarea}».");
        }

        $proveedores = $configuracion->mapWithKeys(fn (IaConfiguracion $c) => [$c->proveedor => $c->modelo])->all();
        $principal = $configuracion->first()->proveedor;

        $generacion = Generacion::create(['user_id' => $usuario?->id, 'tarea' => $tarea, 'estado' => 'pendiente']);
        $inicio = hrtime(true);

        try {
            $respuesta = Image::of($descripcion.' '.self::SIN_TEXTO)
                ->{$formato}()
                ->timeout($configuracion->max('timeout'))
                ->generate(provider: $proveedores);

            $ruta = $respuesta->storePubliclyAs(
                'imagenes/'.($usuario?->id ?? 'sistema'),
                Str::ulid()->toBase32().'.'.Str::after($respuesta->firstImage()->mime(), '/'),
                'public',
            );
        } catch (Throwable $e) {
            $generacion->update([
                'estado' => 'fallida',
                'duracion_ms' => $this->ms($inicio),
                'error' => str($e::class.': '.$e->getMessage())->limit(1000),
            ]);

            throw $e;
        }

        $generacion->update([
            'estado' => 'completada',
            'proveedor' => $respuesta->meta->provider,
            'modelo' => $respuesta->meta->model,
            'respaldo' => $respuesta->meta->provider !== null && $respuesta->meta->provider !== $principal,
            'tokens_entrada' => $respuesta->usage->inputTokens,
            'tokens_salida' => $respuesta->usage->outputTokens,
            'duracion_ms' => $this->ms($inicio),
        ]);

        return ['ruta' => $ruta, 'url' => Storage::disk('public')->url($ruta), 'generacion' => $generacion];
    }

    /**
     * Las IA de imagen cometen errores con los textos, así que se prohíben.
     */
    public const SIN_TEXTO = 'Ilustración sin ningún texto, letras, números ni rótulos.';

    private function ms(int $inicio): int
    {
        return (int) round((hrtime(true) - $inicio) / 1_000_000);
    }
}

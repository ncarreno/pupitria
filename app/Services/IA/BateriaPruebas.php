<?php

namespace App\Services\IA;

use App\Jobs\EjecutarCasoIA;
use App\Models\Clase;
use App\Models\Criterio;
use App\Models\Ficha;
use App\Models\IaCaso;
use App\Models\IaConfiguracion;
use App\Models\IaEjecucion;
use App\Models\Proyecto;
use App\Services\Fichas\GeneradorFicha;
use App\Services\Proyectos\GeneradorProyecto;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Batería de pruebas comparativa: pasa los casos reales por varios proveedores con los mismos
 * generadores que usa la docente, pero sin guardar proyectos ni fichas. Cada llamada se registra
 * en `generaciones` como tarea `bateria`.
 */
class BateriaPruebas
{
    public const PROVEEDORES_POR_DEFECTO = ['anthropic', 'openai', 'gemini'];

    public function __construct(
        private GeneradorProyecto $proyectos,
        private GeneradorFicha $fichas,
    ) {}

    /**
     * Crea una ejecución pendiente por proveedor y lanza un job para cada una.
     *
     * @param  array<int, string>  $proveedores  `anthropic` o `anthropic:modelo`; sin modelo, el de la tarea en la configuración o el del proveedor
     * @return Collection<int, IaEjecucion>
     */
    public function lanzar(IaCaso $caso, array $proveedores = self::PROVEEDORES_POR_DEFECTO): Collection
    {
        // Se comprueban todos antes de crear nada, para no lanzar la mitad.
        $pares = collect($proveedores)->map(fn (string $p) => array_pad(explode(':', trim($p), 2), 2, null));
        $desconocido = $pares->first(fn (array $par) => ! array_key_exists($par[0], IaConfiguracion::PROVEEDORES_TEXTO));

        if ($desconocido) {
            throw new InvalidArgumentException("Proveedor desconocido: «{$desconocido[0]}».");
        }

        return $pares->map(function (array $par) use ($caso) {
            [$proveedor, $modelo] = $par;

            $ejecucion = $caso->ejecuciones()->create([
                'proveedor' => $proveedor,
                'modelo' => $modelo ?: IaConfiguracion::where('tarea', $caso->tarea)->where('proveedor', $proveedor)->value('modelo'),
                'estado' => 'pendiente',
            ]);

            EjecutarCasoIA::dispatch($ejecucion);

            return $ejecucion;
        });
    }

    /**
     * Genera el resultado de una ejecución y lo guarda ya limpio, como lo guardaría la app.
     */
    public function ejecutar(IaEjecucion $ejecucion): void
    {
        $caso = $ejecucion->caso;
        $configuracion = new IaConfiguracion([
            'tarea' => $caso->tarea,
            'proveedor' => $ejecucion->proveedor,
            'modelo' => $ejecucion->modelo,
            'timeout' => IaConfiguracion::timeoutMaximo($caso->tarea),
        ]);

        if ($caso->tarea === 'ficha') {
            $r = $this->fichas->generar($this->ficha($caso), $configuracion);
            $resultado = ['titulo' => $r['titulo'], 'contenido' => $r['contenido'], 'criterios' => $r['criterios']];
        } else {
            $r = $this->proyectos->generar($this->proyecto($caso), soloEsta: $configuracion);
            $resultado = $r['contenido'];
        }

        $ejecucion->update([
            'estado' => 'completada',
            'resultado' => $resultado,
            'error' => null,
            'generacion_id' => $r['generacion']->id,
        ]);
    }

    /**
     * Proyecto sin guardar con los datos del caso; con resultado, listo para `x-proyecto.documento`.
     *
     * @param  array<string, mixed>|null  $resultado
     */
    public function proyecto(IaCaso $caso, ?array $resultado = null, int $descartados = 0): Proyecto
    {
        $e = $caso->entrada;

        $proyecto = new Proyecto([
            'tipo' => $e['tipo'] ?? 'aula',
            'curso' => $e['curso'],
            'areas' => $e['areas'],
            'idea' => $e['idea'],
            'duracion' => $e['duracion'] ?? '6 semanas',
            'contenido' => $resultado,
        ]);
        $proyecto->forceFill(['ids_descartados' => $descartados]);

        return $proyecto->setRelation('user', null)->setRelation('clase', new Clase([
            'curso' => $e['curso'],
            'alumnos' => $e['alumnos'] ?? 20,
            'alumnos_refuerzo' => $e['alumnos_refuerzo'] ?? 0,
            'alumnos_ampliacion' => $e['alumnos_ampliacion'] ?? 0,
        ]));
    }

    /**
     * Ficha sin guardar con los datos del caso; con resultado, lista para `x-ficha.hoja`.
     *
     * @param  array{titulo?: string, contenido?: array<string, mixed>, criterios?: array<string>}|null  $resultado
     */
    public function ficha(IaCaso $caso, ?array $resultado = null): Ficha
    {
        $e = $caso->entrada;

        $ficha = new Ficha([
            'area' => $e['area'],
            'curso' => $e['curso'],
            'tema' => $e['tema'],
            'titulo' => $resultado['titulo'] ?? '',
            'contenido' => $resultado['contenido'] ?? null,
        ]);

        return $ficha->setRelation('user', null)->setRelation('proyecto', null)
            ->setRelation('criterios', Criterio::findMany($resultado['criterios'] ?? []));
    }
}

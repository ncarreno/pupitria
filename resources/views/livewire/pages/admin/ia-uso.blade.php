<?php

use App\Models\Generacion;
use App\Models\IaConfiguracion;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

/**
 * Uso de la IA en un periodo, a partir de `generaciones`: por tarea, cuántas fallan o responde
 * el respaldo; por proveedor y modelo, duración y tokens. Sin costes en euros por ahora.
 */
new #[Layout('layouts.admin')] class extends Component
{
    public const PERIODOS = [7 => 'Últimos 7 días', 30 => 'Últimos 30 días'];

    #[Url]
    public int $dias = 7;

    /**
     * La ruta ya pasa por el middleware `admin`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->esAdministrador(), 403);
    }

    /**
     * Una entrada por tarea. Una generación fallida no tiene proveedor (fallaron todos los de la
     * cadena), así que el desglose por proveedor y modelo es solo de las completadas.
     *
     * @return Collection<string, array{total: int, fallidas: int, sin_terminar: int, respaldo: int, proveedores: Collection<int, array<string, mixed>>}>
     */
    #[Computed]
    public function resumen(): Collection
    {
        $dias = array_key_exists($this->dias, self::PERIODOS) ? $this->dias : 7;

        $grupos = Generacion::query()
            ->where('created_at', '>=', now()->subDays($dias))
            ->selectRaw('tarea, proveedor, modelo, estado, respaldo, count(*) as total, sum(duracion_ms) as duracion,
                sum(tokens_entrada) as entrada, sum(tokens_salida) as salida, sum(tokens_cache) as cache, sum(ids_descartados) as descartados')
            ->groupBy('tarea', 'proveedor', 'modelo', 'estado', 'respaldo')
            ->get();

        $porProveedor = function (Collection $g): array {
            $total = (int) $g->sum('total');

            return [
                'proveedor' => $g->first()->proveedor,
                'modelo' => $g->first()->modelo,
                'total' => $total,
                'duracion_media' => $total ? (int) round($g->sum('duracion') / $total) : 0,
                'entrada' => (int) $g->sum('entrada'),
                'salida' => (int) $g->sum('salida'),
                'cache' => (int) $g->sum('cache'),
                'descartados' => (int) $g->sum('descartados'),
            ];
        };

        $orden = array_flip(array_keys(IaConfiguracion::TAREAS));

        return $grupos->groupBy('tarea')->map(function (Collection $deLaTarea) use ($porProveedor) {
            $completadas = $deLaTarea->where('estado', 'completada');

            return [
                'total' => (int) $deLaTarea->sum('total'),
                'fallidas' => (int) $deLaTarea->where('estado', 'fallida')->sum('total'),
                'sin_terminar' => (int) $deLaTarea->where('estado', 'pendiente')->sum('total'),
                'respaldo' => (int) $completadas->where('respaldo', true)->sum('total'),
                'proveedores' => $completadas->groupBy(fn ($g) => $g->proveedor.'|'.$g->modelo)
                    ->map($porProveedor)->sortByDesc('total')->values(),
            ];
        })->sortBy(fn ($r, string $tarea) => $orden[$tarea] ?? PHP_INT_MAX);
    }

    #[Computed]
    public function ultimas(): Collection
    {
        return Generacion::latest()->latest('id')->limit(25)->get();
    }

    public function numero(int|float|null $n): string
    {
        return number_format((float) $n, 0, ',', '.');
    }

    public function porcentaje(int $parte, int $total): string
    {
        return $total ? round($parte / $total * 100).' %' : '—';
    }

    public function segundos(?int $ms): string
    {
        return $ms === null ? '—' : number_format($ms / 1000, 1, ',', '.').' s';
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Administración" titulo="Uso de la IA">
            Sacado del registro de generaciones. Por ahora en tokens; sin coste en euros.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5">
        <div class="segmentado" role="group" aria-label="Periodo">
            @foreach ($this::PERIODOS as $n => $nombre)
                <button type="button" wire:click="$set('dias', {{ $n }})" aria-pressed="{{ $dias === $n ? 'true' : 'false' }}"
                    @class(['segmento', 'segmento-activo' => $dias === $n])>{{ $nombre }}</button>
            @endforeach
        </div>

        <div class="space-y-5" wire:loading.class="opacity-60" wire:target="dias">
            @if ($this->resumen->isEmpty())
                <x-estado-vacio icono="grafico" titulo="Sin actividad">No hay generaciones en este periodo.</x-estado-vacio>
            @endif

            <div class="grid gap-5 escalonado">
                @foreach ($this->resumen as $tarea => $r)
                    @php
                        $terminadas = $r['total'] - $r['sin_terminar'];
                        $directas = max(0, $terminadas - $r['fallidas'] - $r['respaldo']);
                        $ancho = fn (int $n) => $r['total'] ? round($n / $r['total'] * 100, 2) : 0;
                    @endphp
                    <section wire:key="uso-{{ $tarea }}" class="tarjeta">
                        <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:items-center">
                            <div>
                                <h3 class="tarjeta-titulo">{{ \App\Models\IaConfiguracion::nombreTarea($tarea) }}</h3>
                                <p class="mt-1 text-sm text-stone-600">
                                    <span class="cifra font-medium text-stone-950">{{ $this->numero($r['total']) }}</span> generaciones
                                    · <span @class(['text-red-700 font-medium' => $r['fallidas'] > 0])>{{ $this->porcentaje($r['fallidas'], $terminadas) }} fallidas</span>
                                    · {{ $this->porcentaje($r['respaldo'], $terminadas - $r['fallidas']) }} por el respaldo
                                    @if ($r['sin_terminar']) · {{ $r['sin_terminar'] }} sin terminar @endif
                                </p>
                            </div>
                            <div aria-hidden="true">
                                <div class="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-stone-100">
                                    @if ($directas)<span class="bg-pino-600" style="width: {{ $ancho($directas) }}%"></span>@endif
                                    @if ($r['respaldo'])<span class="bg-amber-400" style="width: {{ $ancho($r['respaldo']) }}%"></span>@endif
                                    @if ($r['fallidas'])<span class="bg-red-500" style="width: {{ $ancho($r['fallidas']) }}%"></span>@endif
                                    @if ($r['sin_terminar'])<span class="bg-stone-300" style="width: {{ $ancho($r['sin_terminar']) }}%"></span>@endif
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500">
                                    <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-pino-600"></i>Principal</span>
                                    <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-amber-400"></i>Respaldo</span>
                                    <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-red-500"></i>Fallidas</span>
                                    @if ($r['sin_terminar'])<span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-stone-300"></i>Sin terminar</span>@endif
                                </div>
                            </div>
                        </div>

                        @if ($r['proveedores']->isNotEmpty())
                            <div class="overflow-x-auto border-t border-stone-100">
                                <table class="tabla">
                                    <thead>
                                        <tr>
                                            <th>Proveedor y modelo</th>
                                            <th class="!text-right">Completadas</th>
                                            <th class="!text-right">Duración media</th>
                                            <th class="!text-right">Tokens entrada</th>
                                            <th class="!text-right">Salida</th>
                                            <th class="!text-right">De caché</th>
                                            <th class="!text-right">Ids descartados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($r['proveedores'] as $p)
                                            <tr>
                                                <td><span class="font-medium text-stone-950">{{ $p['proveedor'] }}</span> <span class="font-mono text-xs text-stone-500">{{ $p['modelo'] }}</span></td>
                                                <td class="cifra text-right">{{ $this->numero($p['total']) }}</td>
                                                <td class="cifra text-right">{{ $this->segundos($p['duracion_media']) }}</td>
                                                <td class="cifra text-right">{{ $this->numero($p['entrada']) }}</td>
                                                <td class="cifra text-right">{{ $this->numero($p['salida']) }}</td>
                                                <td class="cifra text-right">{{ $this->numero($p['cache']) }}</td>
                                                <td class="cifra text-right">{{ $this->numero($p['descartados']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            <section class="tarjeta">
                <div class="tarjeta-cabecera">
                    <h3 class="tarjeta-titulo">Últimas generaciones</h3>
                    <x-icono nombre="historial" class="size-4 text-stone-400" />
                </div>
                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tarea</th>
                                <th>Proveedor y modelo</th>
                                <th>Estado</th>
                                <th class="!text-right">Duración</th>
                                <th class="!text-right">Tokens</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->ultimas as $g)
                                <tr wire:key="g-{{ $g->id }}" class="align-top">
                                    <td class="cifra whitespace-nowrap text-stone-500">{{ $g->created_at->format('d/m H:i') }}</td>
                                    <td class="text-stone-800">{{ \App\Models\IaConfiguracion::nombreTarea($g->tarea) }}</td>
                                    <td>{{ $g->proveedor ?? '—' }} <span class="font-mono text-xs text-stone-500">{{ $g->modelo }}</span>@if ($g->respaldo) <span class="insignia-ambar ms-1">respaldo</span>@endif</td>
                                    <td>
                                        <span @class([
                                            'insignia',
                                            'bg-red-50 text-red-700 ring-red-200' => $g->estado === 'fallida',
                                            'bg-stone-100 text-stone-600 ring-stone-200' => $g->estado === 'pendiente',
                                            'bg-pino-50 text-pino-800 ring-pino-200' => ! in_array($g->estado, ['fallida', 'pendiente']),
                                        ])>{{ $g->estado }}</span>
                                        @if ($g->error)
                                            <p class="mt-1.5 max-w-md break-all text-xs text-red-700">{{ str($g->error)->limit(200) }}</p>
                                        @endif
                                    </td>
                                    <td class="cifra whitespace-nowrap text-right">{{ $this->segundos($g->duracion_ms) }}</td>
                                    <td class="cifra whitespace-nowrap text-right">{{ $this->numero($g->tokens_entrada) }} / {{ $this->numero($g->tokens_salida) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-stone-500">Todavía no hay generaciones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

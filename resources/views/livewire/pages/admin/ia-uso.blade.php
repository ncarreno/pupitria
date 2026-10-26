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
new #[Layout('layouts.app')] class extends Component
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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Uso de la IA</h2>
                <p class="text-sm text-gray-500 mt-1">Sacado del registro de generaciones. Por ahora en tokens; sin coste en euros.</p>
            </div>
            <a href="{{ route('admin.ia') }}" wire:navigate class="text-sm text-indigo-700 hover:underline">Configuración</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="inline-flex rounded-md border border-gray-300 bg-white overflow-hidden" role="group" aria-label="Periodo">
                @foreach ($this::PERIODOS as $n => $nombre)
                    <button type="button" wire:click="$set('dias', {{ $n }})" aria-pressed="{{ $dias === $n ? 'true' : 'false' }}"
                        class="px-4 py-2 text-sm {{ $dias === $n ? 'bg-gray-800 text-white font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">{{ $nombre }}</button>
                @endforeach
            </div>

            @if ($this->resumen->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">No hay generaciones en este periodo.</div>
            @endif

            @foreach ($this->resumen as $tarea => $r)
                @php($terminadas = $r['total'] - $r['sin_terminar'])
                <section wire:key="uso-{{ $tarea }}" class="bg-white shadow-sm sm:rounded-lg p-5 space-y-3">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ \App\Models\IaConfiguracion::TAREAS[$tarea] ?? $tarea }}</h3>
                        <p class="text-sm text-gray-600">
                            {{ $this->numero($r['total']) }} generaciones
                            · <span @class(['text-red-700 font-medium' => $r['fallidas'] > 0])>{{ $this->porcentaje($r['fallidas'], $terminadas) }} fallidas</span>
                            · {{ $this->porcentaje($r['respaldo'], $terminadas - $r['fallidas']) }} por el respaldo
                            @if ($r['sin_terminar']) · {{ $r['sin_terminar'] }} sin terminar @endif
                        </p>
                    </div>

                    @if ($r['proveedores']->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="py-2 pe-4">Proveedor y modelo</th>
                                        <th class="py-2 pe-4 text-right">Completadas</th>
                                        <th class="py-2 pe-4 text-right">Duración media</th>
                                        <th class="py-2 pe-4 text-right">Tokens entrada</th>
                                        <th class="py-2 pe-4 text-right">Salida</th>
                                        <th class="py-2 pe-4 text-right">De caché</th>
                                        <th class="py-2 text-right">Ids descartados</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($r['proveedores'] as $p)
                                        <tr>
                                            <td class="py-2 pe-4"><span class="font-medium text-gray-900">{{ $p['proveedor'] }}</span> <span class="text-gray-500">{{ $p['modelo'] }}</span></td>
                                            <td class="py-2 pe-4 text-right">{{ $this->numero($p['total']) }}</td>
                                            <td class="py-2 pe-4 text-right">{{ $this->segundos($p['duracion_media']) }}</td>
                                            <td class="py-2 pe-4 text-right">{{ $this->numero($p['entrada']) }}</td>
                                            <td class="py-2 pe-4 text-right">{{ $this->numero($p['salida']) }}</td>
                                            <td class="py-2 pe-4 text-right">{{ $this->numero($p['cache']) }}</td>
                                            <td class="py-2 text-right">{{ $this->numero($p['descartados']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach

            <section class="bg-white shadow-sm sm:rounded-lg p-5 space-y-3">
                <h3 class="font-semibold text-gray-900">Últimas generaciones</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="py-2 pe-4">Fecha</th>
                                <th class="py-2 pe-4">Tarea</th>
                                <th class="py-2 pe-4">Proveedor y modelo</th>
                                <th class="py-2 pe-4">Estado</th>
                                <th class="py-2 pe-4 text-right">Duración</th>
                                <th class="py-2 text-right">Tokens</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($this->ultimas as $g)
                                <tr wire:key="g-{{ $g->id }}" class="align-top">
                                    <td class="py-2 pe-4 whitespace-nowrap text-gray-600">{{ $g->created_at->format('d/m H:i') }}</td>
                                    <td class="py-2 pe-4">{{ \App\Models\IaConfiguracion::TAREAS[$g->tarea] ?? $g->tarea }}</td>
                                    <td class="py-2 pe-4">{{ $g->proveedor ?? '—' }} <span class="text-gray-500">{{ $g->modelo }}</span>@if ($g->respaldo) <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">respaldo</span>@endif</td>
                                    <td class="py-2 pe-4">
                                        <span @class(['text-red-700' => $g->estado === 'fallida', 'text-gray-500' => $g->estado === 'pendiente'])>{{ $g->estado }}</span>
                                        @if ($g->error)
                                            <p class="mt-1 text-xs text-red-700 break-all">{{ str($g->error)->limit(200) }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4 text-right whitespace-nowrap">{{ $this->segundos($g->duracion_ms) }}</td>
                                    <td class="py-2 text-right whitespace-nowrap">{{ $this->numero($g->tokens_entrada) }} / {{ $this->numero($g->tokens_salida) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-3 text-gray-500">Todavía no hay generaciones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

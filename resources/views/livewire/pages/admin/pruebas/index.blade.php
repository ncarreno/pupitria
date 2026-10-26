<?php

use App\Models\IaCaso;
use App\Models\IaConfiguracion;
use App\Services\IA\BateriaPruebas;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Casos de la batería de pruebas. El administrador los lanza con los proveedores elegidos;
 * quien valora (el administrador y la beta tester) ve cuántos le quedan por valorar.
 */
new #[Layout('layouts.app')] class extends Component
{
    /** @var array<int, string> */
    public array $proveedores = BateriaPruebas::PROVEEDORES_POR_DEFECTO;

    public ?string $aviso = null;

    /**
     * La ruta ya pasa por `can:valorar-pruebas`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(Gate::allows('valorar-pruebas'), 403);
    }

    #[Computed]
    public function casos()
    {
        return IaCaso::orderBy('tarea', 'desc')->orderBy('nombre')->get();
    }

    #[Computed]
    public function hayPendientes(): bool
    {
        return $this->casos->contains(fn (IaCaso $c) => $c->ultimasEjecuciones()->contains->estaPendiente());
    }

    /**
     * Lanza un caso o, sin id, todos. Solo el administrador: gasta saldo de API.
     */
    public function lanzar(?int $casoId = null): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
        $bateria = app(BateriaPruebas::class);

        $this->validate(
            ['proveedores' => ['required', 'array', 'min:1'], 'proveedores.*' => ['in:'.implode(',', array_keys(IaConfiguracion::PROVEEDORES_TEXTO))]],
            ['proveedores.required' => 'Elige al menos un proveedor.', 'proveedores.min' => 'Elige al menos un proveedor.'],
        );

        $casos = $casoId ? IaCaso::whereKey($casoId)->get() : IaCaso::all();
        foreach ($casos as $caso) {
            $bateria->lanzar($caso, $this->proveedores);
        }

        $this->aviso = $casos->count() * count($this->proveedores).' ejecuciones en cola. Aparecen aquí según terminan.';
        unset($this->casos, $this->hayPendientes);
    }
}; ?>

<div @if ($this->hayPendientes) wire:poll.5s @endif>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Batería de pruebas</h2>
                <p class="text-sm text-gray-500 mt-1">Los mismos casos con varios proveedores, para comparar a ciegas antes de cambiar de proveedor.</p>
            </div>
            @if (auth()->user()->esAdministrador())
                <div class="flex gap-4 text-sm">
                    <a href="{{ route('admin.ia') }}" wire:navigate class="text-indigo-700 hover:underline">Configuración</a>
                    <a href="{{ route('admin.ia.uso') }}" wire:navigate class="text-indigo-700 hover:underline">Uso de la IA</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (auth()->user()->esAdministrador())
                <section class="bg-white shadow-sm sm:rounded-lg p-5 space-y-3">
                    <h3 class="font-semibold text-gray-900">Lanzar</h3>
                    <p class="text-sm text-gray-600">Cada caso se genera una vez con cada proveedor elegido, con el modelo de la configuración o, si no hay, el del proveedor. Gasta saldo de API.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\IaConfiguracion::PROVEEDORES_TEXTO as $clave => $nombre)
                            <label wire:key="pv-{{ $clave }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm cursor-pointer {{ in_array($clave, $proveedores) ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700' }}">
                                <input type="checkbox" wire:model.live="proveedores" value="{{ $clave }}" class="rounded border-gray-300 text-indigo-600">
                                {{ $nombre }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="array_merge($errors->get('proveedores'), $errors->get('proveedores.*'))" />
                    <div class="flex flex-wrap items-center gap-3">
                        <x-primary-button wire:click="lanzar" wire:loading.attr="disabled" wire:confirm="¿Lanzar todos los casos con los proveedores elegidos?">Lanzar todos los casos</x-primary-button>
                        @if ($aviso)
                            <span class="text-sm text-green-700">{{ $aviso }}</span>
                        @endif
                    </div>
                </section>
            @endif

            @if ($this->casos->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">Todavía no hay casos. Se crean con <code>php artisan db:seed --class=IaCasosSeeder</code>.</div>
            @else
                <ul class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    @foreach ($this->casos as $caso)
                        @php
                            $ultimas = $caso->ultimasEjecuciones();
                            $completadas = $ultimas->where('estado', 'completada');
                            $porValorar = $completadas->filter(fn ($e) => ! $e->valoracionDe(auth()->user()))->count();
                        @endphp
                        <li wire:key="caso-{{ $caso->id }}" class="px-5 py-4 flex flex-wrap items-baseline justify-between gap-3">
                            <div>
                                <a href="{{ route('admin.pruebas.ver', $caso) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $caso->nombre }}</a>
                                <p class="text-sm text-gray-600">
                                    {{ \App\Models\IaCaso::TAREAS[$caso->tarea] ?? $caso->tarea }}
                                    · {{ $completadas->count() }} {{ $completadas->count() === 1 ? 'resultado' : 'resultados' }}
                                    @if ($ultimas->contains->estaPendiente()) · <span class="text-indigo-700">generando</span> @endif
                                    @if ($ultimas->where('estado', 'fallida')->isNotEmpty()) · <span class="text-red-700">{{ $ultimas->where('estado', 'fallida')->count() }} fallidas</span> @endif
                                    @if ($porValorar) · <span class="font-medium text-amber-800">{{ $porValorar }} por valorar</span> @endif
                                </p>
                            </div>
                            @if (auth()->user()->esAdministrador())
                                <button type="button" wire:click="lanzar({{ $caso->id }})" wire:loading.attr="disabled" class="text-sm text-indigo-700 hover:underline">Lanzar este caso</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

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
new #[Layout('layouts.admin')] class extends Component
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
        <x-cabecera antetitulo="Administración" titulo="Batería de pruebas">
            Los mismos casos con varios proveedores, para comparar a ciegas antes de cambiar de proveedor.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5">
        @if (auth()->user()->esAdministrador())
            <section class="tarjeta p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <h3 class="tarjeta-titulo">Lanzar</h3>
                        <p class="mt-1 max-w-[70ch] text-sm text-stone-600">Cada caso se genera una vez con cada proveedor elegido, con el modelo de la configuración o, si no hay, el del proveedor. Gasta saldo de API.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (\App\Models\IaConfiguracion::PROVEEDORES_TEXTO as $clave => $nombre)
                                @php $elegido = in_array($clave, $proveedores); @endphp
                                <label wire:key="pv-{{ $clave }}" @class(['chip', 'chip-activo' => $elegido, 'chip-inactivo' => ! $elegido])>
                                    <input type="checkbox" wire:model.live="proveedores" value="{{ $clave }}" class="sr-only">
                                    <x-icono :nombre="$elegido ? 'check' : 'mas'" @class(['size-3.5', 'text-pino-700' => $elegido, 'text-stone-400' => ! $elegido]) />
                                    {{ $nombre }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-3" :messages="array_merge($errors->get('proveedores'), $errors->get('proveedores.*'))" />
                    </div>
                    <x-primary-button wire:click="lanzar" wire:loading.attr="disabled" wire:confirm="¿Lanzar todos los casos con los proveedores elegidos?"><x-icono nombre="matraz" class="size-4" />Lanzar todos los casos</x-primary-button>
                </div>
                @if ($aviso)
                    <div class="aviso-pino mt-4 animate-aparecer"><x-icono nombre="check-circulo" class="size-5 text-pino-700" />{{ $aviso }}</div>
                @endif
            </section>
        @endif

        @if ($this->casos->isEmpty())
            <x-estado-vacio icono="matraz" titulo="Sin casos">Todavía no hay casos. Se crean con <code class="rounded bg-stone-100 px-1.5 py-0.5 font-mono text-[13px]">php artisan db:seed --class=IaCasosSeeder</code>.</x-estado-vacio>
        @else
            <ul class="tarjeta divide-y divide-stone-100 overflow-hidden escalonado">
                @foreach ($this->casos as $caso)
                    @php
                        $ultimas = $caso->ultimasEjecuciones();
                        $completadas = $ultimas->where('estado', 'completada');
                        $porValorar = $completadas->filter(fn ($e) => ! $e->valoracionDe(auth()->user()))->count();
                    @endphp
                    <li wire:key="caso-{{ $caso->id }}" class="group flex flex-wrap items-center gap-4 px-5 py-4 transition hover:bg-stone-50/70 sm:px-6">
                        <span class="hidden size-10 shrink-0 items-center justify-center rounded-xl bg-stone-50 text-stone-500 ring-1 ring-inset ring-stone-200 sm:inline-flex">
                            <x-icono :nombre="$caso->tarea === 'ficha' ? 'ficha' : 'tablero'" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.pruebas.ver', $caso) }}" wire:navigate class="font-medium text-stone-950 hover:underline hover:decoration-stone-300 hover:underline-offset-4">{{ $caso->nombre }}</a>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-stone-500">
                                <span>{{ \App\Models\IaCaso::TAREAS[$caso->tarea] ?? $caso->tarea }}</span>
                                <span class="text-stone-300">·</span>
                                <span>{{ $completadas->count() }} {{ $completadas->count() === 1 ? 'resultado' : 'resultados' }}</span>
                                @if ($ultimas->contains->estaPendiente()) <span class="insignia-pino"><span class="punto-vivo size-1.5"></span>generando</span> @endif
                                @if ($ultimas->where('estado', 'fallida')->isNotEmpty()) <span class="insignia-rojo">{{ $ultimas->where('estado', 'fallida')->count() }} fallidas</span> @endif
                                @if ($porValorar) <span class="insignia-ambar">{{ $porValorar }} por valorar</span> @endif
                            </p>
                        </div>
                        @if (auth()->user()->esAdministrador())
                            <button type="button" wire:click="lanzar({{ $caso->id }})" wire:loading.attr="disabled" class="boton-secundario boton-sm">Lanzar este caso</button>
                        @endif
                        <a href="{{ route('admin.pruebas.ver', $caso) }}" wire:navigate class="boton-icono" aria-label="Abrir {{ $caso->nombre }}"><x-icono nombre="chevron-derecha" class="size-4" /></a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

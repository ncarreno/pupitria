<?php

use App\Models\Area;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Computed]
    public function fichas()
    {
        return auth()->user()->fichas()->with('proyecto')->get();
    }

    #[Computed]
    public function areas()
    {
        return Area::pluck('nombre_corto', 'codigo');
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Tu trabajo" titulo="Fichas">
            Cada ficha tiene tres niveles sobre el mismo contenido. Se editan con un clic y se imprimen tal cual.
            <x-slot:acciones>
                <a href="{{ route('fichas.nueva') }}" wire:navigate class="boton-primario"><x-icono nombre="mas" class="size-4" />Nueva ficha</a>
            </x-slot:acciones>
        </x-cabecera>
    </x-slot>

    <div class="contenedor">
        @if ($this->fichas->isEmpty())
            <x-estado-vacio icono="capas" titulo="Una ficha, tres niveles">
                Todavía no tienes fichas. Cada ficha sale en tres niveles (refuerzo, estándar y ampliación) sobre el mismo contenido, para que toda la clase trabaje lo mismo a la vez.
                <x-slot:acciones>
                    <a href="{{ route('fichas.nueva') }}" wire:navigate class="boton-primario"><x-icono nombre="chispa" class="size-4" />Crear una ficha</a>
                </x-slot:acciones>
            </x-estado-vacio>
        @else
            <ul class="tarjeta divide-y divide-stone-100 overflow-hidden escalonado">
                @foreach ($this->fichas as $f)
                    <li wire:key="f-{{ $f->id }}">
                        <a href="{{ route('fichas.ver', $f) }}" wire:navigate class="group flex items-center gap-4 px-5 py-4 transition hover:bg-stone-50/80 sm:px-6">
                            <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-stone-50 text-stone-500 ring-1 ring-inset ring-stone-200 transition group-hover:bg-pino-50 group-hover:text-pino-700 group-hover:ring-pino-200 sm:inline-flex">
                                <x-icono nombre="ficha" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-stone-950">{{ $f->titulo ?: ($f->estaGenerando() ? 'Generando…' : str($f->tema)->limit(60)) }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-stone-500">
                                    <span>{{ $this->areas[$f->area] ?? $f->area }} · {{ $f->curso }}.º</span>
                                    @if ($f->proyecto)
                                        <span class="text-stone-300">·</span>
                                        <span class="inline-flex min-w-0 items-center gap-1"><x-icono nombre="tablero" class="size-3.5 text-stone-400" /><span class="truncate">{{ $f->proyecto->titulo }}</span></span>
                                    @endif
                                    <span class="text-stone-300">·</span>
                                    <span class="cifra">{{ $f->updated_at->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            @if ($f->estado === 'generando')
                                <span class="insignia-pino"><span class="punto-vivo size-1.5"></span>generando</span>
                            @endif
                            @if ($f->estado === 'error')
                                <span class="insignia-rojo"><x-icono nombre="aviso" class="size-3" />error</span>
                            @endif
                            <x-icono nombre="chevron-derecha" class="size-4 text-stone-300 transition duration-300 ease-suave group-hover:translate-x-0.5 group-hover:text-stone-500" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

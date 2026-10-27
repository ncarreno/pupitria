<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Computed]
    public function proyectos()
    {
        return auth()->user()->proyectos()->with('clase')->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Tu trabajo" titulo="Proyectos">
            Cada proyecto guarda sus versiones y las fichas que saques de sus tareas.
            <x-slot:acciones>
                <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario"><x-icono nombre="mas" class="size-4" />Nuevo proyecto</a>
            </x-slot:acciones>
        </x-cabecera>
    </x-slot>

    <div class="contenedor">
        @if ($this->proyectos->isEmpty())
            <x-estado-vacio icono="tablero" titulo="Tu primer proyecto empieza con una frase">
                Todavía no tienes proyectos. Escribe tu idea en una frase y la app prepara la ficha completa con criterios reales del currículo.
                <x-slot:acciones>
                    <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario"><x-icono nombre="chispa" class="size-4" />Crear un proyecto</a>
                </x-slot:acciones>
            </x-estado-vacio>
        @else
            <ul class="tarjeta divide-y divide-stone-100 overflow-hidden escalonado">
                @foreach ($this->proyectos as $p)
                    <li wire:key="p-{{ $p->id }}">
                        <a href="{{ route('proyectos.ver', $p) }}" wire:navigate class="group flex items-center gap-4 px-5 py-4 transition hover:bg-stone-50/80 sm:px-6">
                            <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-stone-50 text-stone-500 ring-1 ring-inset ring-stone-200 transition group-hover:bg-pino-50 group-hover:text-pino-700 group-hover:ring-pino-200 sm:inline-flex">
                                <x-icono nombre="tablero" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-stone-950">{{ $p->titulo ?: ($p->estaGenerando() ? 'Generando…' : str($p->idea)->limit(60)) }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-stone-500">
                                    <span>{{ $p->clase?->nombre ?? $p->curso.'.º' }}</span>
                                    <span class="text-stone-300">·</span>
                                    <span>{{ $p->tipo === 'centro' ? 'Centro' : 'Aula' }}</span>
                                    <span class="text-stone-300">·</span>
                                    <span class="cifra">{{ $p->updated_at->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            @if ($p->estado === 'generando')
                                <span class="insignia-pino"><span class="punto-vivo size-1.5"></span>generando</span>
                            @endif
                            @if ($p->estado === 'error')
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

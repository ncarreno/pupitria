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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proyectos</h2>
            <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Nuevo proyecto</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($this->proyectos->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    Todavía no tienes proyectos. Escribe tu idea en una frase y la app prepara la ficha completa con criterios reales del currículo.
                </div>
            @else
                <ul class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    @foreach ($this->proyectos as $p)
                        <li wire:key="p-{{ $p->id }}">
                            <a href="{{ route('proyectos.ver', $p) }}" wire:navigate class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-4 hover:bg-gray-50">
                                <span class="font-medium text-gray-900">{{ $p->titulo ?: ($p->estaGenerando() ? 'Generando…' : str($p->idea)->limit(60)) }}</span>
                                <span class="text-sm text-gray-600">
                                    {{ $p->clase?->nombre ?? $p->curso.'.º' }}
                                    · {{ $p->tipo === 'centro' ? 'Centro' : 'Aula' }}
                                    · {{ $p->updated_at->format('d/m/Y') }}
                                    @if ($p->estado === 'generando') · <span class="text-indigo-700">generando</span> @endif
                                    @if ($p->estado === 'error') · <span class="text-red-700">error</span> @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

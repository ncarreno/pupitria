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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fichas</h2>
            <a href="{{ route('fichas.nueva') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Nueva ficha</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($this->fichas->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    Todavía no tienes fichas. Cada ficha sale en tres niveles (refuerzo, estándar y ampliación) sobre el mismo contenido, para que toda la clase trabaje lo mismo a la vez.
                </div>
            @else
                <ul class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    @foreach ($this->fichas as $f)
                        <li wire:key="f-{{ $f->id }}">
                            <a href="{{ route('fichas.ver', $f) }}" wire:navigate class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-4 hover:bg-gray-50">
                                <span class="font-medium text-gray-900">{{ $f->titulo ?: ($f->estaGenerando() ? 'Generando…' : str($f->tema)->limit(60)) }}</span>
                                <span class="text-sm text-gray-600">
                                    {{ $this->areas[$f->area] ?? $f->area }} · {{ $f->curso }}.º
                                    @if ($f->proyecto) · {{ $f->proyecto->titulo }} @endif
                                    · {{ $f->updated_at->format('d/m/Y') }}
                                    @if ($f->estado === 'generando') · <span class="text-indigo-700">generando</span> @endif
                                    @if ($f->estado === 'error') · <span class="text-red-700">error</span> @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

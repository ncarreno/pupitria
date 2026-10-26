<?php

use App\Models\Area;
use App\Models\CompetenciaEspecifica;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $area = 'LCL';

    #[Url]
    public int $ciclo = 2;

    #[Url]
    public string $buscar = '';

    #[Computed]
    public function areas()
    {
        return Area::orderBy('orden')->get(['codigo', 'nombre_corto', 'cursos']);
    }

    #[Computed]
    public function competencias()
    {
        $buscar = trim($this->buscar);

        return CompetenciaEspecifica::query()
            ->whereHas('area', fn ($q) => $q->where('codigo', $this->area))
            ->with([
                'descriptores',
                'criterios' => fn ($q) => $q->where('ciclo', $this->ciclo)
                    ->when($buscar !== '', fn ($q) => $q->where('texto', 'like', "%{$buscar}%")),
            ])
            ->orderBy('numero')
            ->get();
    }

    #[Computed]
    public function saberes()
    {
        $buscar = trim($this->buscar);

        return Area::where('codigo', $this->area)->first()?->saberes()
            ->where('ciclo', $this->ciclo)
            ->when($buscar !== '', fn ($q) => $q->where('texto', 'like', "%{$buscar}%"))
            ->get()
            ->groupBy('bloque') ?? collect();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Currículo de Primaria · Asturias</h2>
        <p class="text-sm text-gray-500 mt-1">Decreto 57/2022, de 5 de agosto (BOPA núm. 156, 12-VIII-2022)</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-4 items-end">
                <label class="grid gap-1 text-sm font-medium text-gray-700">Área
                    <select wire:model.live="area" class="rounded-md border-gray-300">
                        @foreach ($this->areas as $a)
                            <option value="{{ $a->codigo }}">{{ $a->nombre_corto }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm font-medium text-gray-700">Ciclo
                    <select wire:model.live="ciclo" class="rounded-md border-gray-300">
                        <option value="1">1.º ciclo (1.º-2.º)</option>
                        <option value="2">2.º ciclo (3.º-4.º)</option>
                        <option value="3">3.º ciclo (5.º-6.º)</option>
                    </select>
                </label>
                <label class="grid gap-1 text-sm font-medium text-gray-700 flex-1 min-w-48">Buscar
                    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Por ejemplo: textos orales" class="rounded-md border-gray-300">
                </label>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <section class="bg-white shadow-sm sm:rounded-lg p-5 space-y-5">
                    <h3 class="font-semibold text-gray-900">Competencias específicas y criterios de evaluación</h3>
                    @forelse ($this->competencias as $c)
                        @continue($c->criterios->isEmpty() && $buscar !== '')
                        <div class="space-y-2">
                            <p class="text-sm text-gray-800"><span class="font-semibold">CE{{ $c->numero }}.</span> {{ $c->enunciado }}</p>
                            <p class="text-xs text-gray-500">Descriptores: {{ $c->descriptores->pluck('codigo')->implode(', ') }}</p>
                            <ul class="space-y-1">
                                @foreach ($c->criterios as $cr)
                                    <li class="text-sm text-gray-700 flex gap-2">
                                        <span class="font-mono text-xs text-teal-700 shrink-0 pt-0.5">{{ $cr->codigo }}</span>
                                        <span>{{ $cr->texto }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay competencias para esta área.</p>
                    @endforelse
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg p-5 space-y-5">
                    <h3 class="font-semibold text-gray-900">Saberes básicos</h3>
                    @forelse ($this->saberes as $bloque => $items)
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-gray-800">{{ $bloque }}</p>
                            @foreach ($items->groupBy('apartado') as $apartado => $saberes)
                                @if ($apartado)
                                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $apartado }}</p>
                                @endif
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($saberes as $s)
                                        <li class="text-sm text-gray-700">{{ $s->texto }}</li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay saberes que coincidan.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</div>

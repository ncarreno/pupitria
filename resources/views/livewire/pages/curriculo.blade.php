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
        <x-cabecera antetitulo="Referencia oficial" titulo="Currículo de Primaria · Asturias">
            <p>Decreto 57/2022, de 5 de agosto (BOPA núm. 156, 12-VIII-2022)</p>
            <p class="mt-1 text-sm text-stone-500">La misma base que usa la app para comprobar cada criterio y cada saber de tus proyectos y fichas.</p>
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-6">
        <div class="tarjeta sticky top-[4.5rem] z-30 grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)] sm:p-5">
            <label class="grid gap-2">
                <span class="etiqueta">Área</span>
                <select wire:model.live="area" class="campo">
                    @foreach ($this->areas as $a)
                        <option value="{{ $a->codigo }}">{{ $a->nombre_corto }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-2">
                <span class="etiqueta">Ciclo</span>
                <select wire:model.live="ciclo" class="campo">
                    <option value="1">1.º ciclo (1.º-2.º)</option>
                    <option value="2">2.º ciclo (3.º-4.º)</option>
                    <option value="3">3.º ciclo (5.º-6.º)</option>
                </select>
            </label>
            <label class="grid gap-2">
                <span class="etiqueta">Buscar</span>
                <span class="relative">
                    <x-icono nombre="buscar" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Por ejemplo: textos orales" class="campo ps-10">
                    <span wire:loading.delay class="absolute right-3.5 top-1/2 -translate-y-1/2"><span class="punto-vivo text-pino-600"></span></span>
                </span>
            </label>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]" wire:loading.class="opacity-60" wire:target="area,ciclo,buscar">
            <section class="tarjeta">
                <div class="tarjeta-cabecera">
                    <h3 class="tarjeta-titulo">Competencias específicas y criterios de evaluación</h3>
                </div>
                <div class="divide-y divide-stone-100">
                    @forelse ($this->competencias as $c)
                        @continue($c->criterios->isEmpty() && $buscar !== '')
                        <div class="space-y-3 px-5 py-5 sm:px-6">
                            <div class="flex gap-3">
                                <span class="cifra inline-flex h-7 shrink-0 items-center rounded-lg bg-stone-900 px-2 text-xs font-medium text-white">CE{{ $c->numero }}</span>
                                <p class="text-sm leading-relaxed text-stone-800">{{ $c->enunciado }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1 ps-[3.25rem]">
                                <span class="me-1 text-xs text-stone-500">Descriptores:</span>
                                @foreach ($c->descriptores as $d)
                                    <span class="rounded-md bg-stone-100 px-1.5 py-0.5 font-mono text-[11px] text-stone-600">{{ $d->codigo }}</span>
                                @endforeach
                            </div>
                            @if ($c->criterios->isNotEmpty())
                                <ul class="space-y-2 ps-[3.25rem]">
                                    @foreach ($c->criterios as $cr)
                                        <li class="flex gap-3 text-sm leading-relaxed text-stone-700">
                                            <span class="codigo mt-0.5">{{ $cr->codigo }}</span>
                                            <span>{{ $cr->texto }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-stone-500">No hay competencias para esta área.</p>
                    @endforelse
                </div>
            </section>

            <section class="tarjeta">
                <div class="tarjeta-cabecera">
                    <h3 class="tarjeta-titulo">Saberes básicos</h3>
                </div>
                <div class="divide-y divide-stone-100">
                    @forelse ($this->saberes as $bloque => $items)
                        <div class="space-y-3 px-5 py-5 sm:px-6">
                            <p class="text-sm font-semibold text-stone-950">{{ $bloque }}</p>
                            @foreach ($items->groupBy('apartado') as $apartado => $saberes)
                                @if ($apartado)
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-pino-700">{{ $apartado }}</p>
                                @endif
                                <ul class="space-y-1.5">
                                    @foreach ($saberes as $s)
                                        <li class="flex gap-2.5 text-sm leading-relaxed text-stone-700"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-stone-300"></span>{{ $s->texto }}</li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-stone-500">No hay saberes que coincidan.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>

<?php

use App\Jobs\GenerarFicha;
use App\Models\Area;
use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Proyecto;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public ?int $proyecto = null;

    #[Url]
    public ?int $tarea = null;

    public ?int $claseId = null;

    public string $area = '';

    public string $tema = '';

    /** ninguna | openai | prompt (ver Ficha::ILUSTRACIONES) */
    public string $ilustracion = 'ninguna';

    public function mount(): void
    {
        $this->proyecto ??= request()->integer('proyecto') ?: null;
        $this->tarea ??= request()->has('tarea') ? request()->integer('tarea') : null;

        $proyecto = $this->proyectoElegido;
        $this->claseId = $proyecto?->clase_id ?? $this->clases->first()?->id;

        $tarea = $proyecto && $this->tarea !== null ? data_get($proyecto->contenido, "tareas.{$this->tarea}") : null;
        if (! is_array($tarea)) {
            $this->tarea = null;
        }
        $this->area = collect($tarea['areas'] ?? [])->first(fn ($a) => in_array($a, $this->areasDisponibles->keys()->all(), true))
            ?? $this->areasDisponibles->keys()->first() ?? '';
        $this->tema = $tarea ? "{$tarea['titulo']}: {$tarea['descripcion']}" : '';
    }

    #[Computed]
    public function clases()
    {
        return auth()->user()->clases()->with('areas')->get();
    }

    #[Computed]
    public function proyectoElegido(): ?Proyecto
    {
        if (! $this->proyecto) {
            return null;
        }

        $p = Proyecto::find($this->proyecto);

        return $p && auth()->user()->can('view', $p) ? $p : null;
    }

    #[Computed]
    public function areasDisponibles()
    {
        $clase = $this->clases->firstWhere('id', $this->claseId);

        return ($clase?->areas ?? collect())->pluck('nombre_corto', 'codigo');
    }

    public function updatedClaseId(): void
    {
        if (! $this->areasDisponibles->has($this->area)) {
            $this->area = $this->areasDisponibles->keys()->first() ?? '';
        }
    }

    public function crear(): void
    {
        $this->validate([
            'claseId' => ['required', 'integer'],
            'area' => ['required', 'string'],
            'tema' => ['required', 'string', 'min:5', 'max:1000'],
            'ilustracion' => ['required', 'in:'.implode(',', array_keys(Ficha::ILUSTRACIONES))],
        ], [
            'tema.required' => 'Escribe qué quieres trabajar.',
            'tema.min' => 'Cuéntalo un poco más.',
        ]);

        $clase = Clase::with('areas')->findOrFail($this->claseId);
        $this->authorize('view', $clase);

        if (! $clase->areas->contains('codigo', $this->area)) {
            $this->addError('area', 'Elige un área que impartas en esta clase.');

            return;
        }

        $proyecto = $this->proyectoElegido;
        $tareaExiste = $proyecto && $this->tarea !== null && $this->tarea >= 0
            && is_array(data_get($proyecto->contenido, "tareas.{$this->tarea}"));

        $ficha = auth()->user()->fichas()->create([
            'clase_id' => $clase->id,
            'proyecto_id' => $proyecto?->id,
            'tarea_indice' => $tareaExiste ? $this->tarea : null,
            'area' => $this->area,
            'curso' => $clase->curso,
            'tema' => $this->tema,
            'con_imagen' => $this->ilustracion !== 'ninguna',
            'imagen_por' => $this->ilustracion === 'prompt' ? 'prompt' : 'openai',
            'estado' => 'generando',
        ]);

        GenerarFicha::dispatch($ficha);

        $this->redirectRoute('fichas.ver', $ficha, navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva ficha</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($this->clases->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3 text-sm text-gray-700">
                    <p>Antes de crear una ficha, configura tu clase.</p>
                    <a href="{{ route('clases') }}" wire:navigate class="text-indigo-700 hover:underline">Configurar mi clase</a>
                </div>
            @else
                <form wire:submit="crear" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    @if ($this->proyectoElegido)
                        <p class="text-sm text-gray-600">Para el proyecto <span class="font-medium text-gray-900">{{ $this->proyectoElegido->titulo }}</span>@if ($tarea !== null), tarea {{ $tarea + 1 }}@endif.</p>
                    @endif

                    <div>
                        <x-input-label for="tema" value="Qué quieres trabajar" />
                        <p class="text-xs text-gray-500">Tema concreto, tipo de actividad o lo que no quieres.</p>
                        <textarea wire:model="tema" id="tema" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Animales de Oceanía y cómo se adaptan a su ecosistema. Con una lectura corta y nada de casillas vacías."></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('tema')" />
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="claseId" value="Clase" />
                            <select wire:model.live="claseId" id="claseId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($this->clases as $cl)
                                    <option value="{{ $cl->id }}">{{ $cl->nombre }} ({{ $cl->curso }}.º)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="area" value="Área" />
                            <select wire:model="area" id="area" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($this->areasDisponibles as $codigo => $nombre)
                                    <option value="{{ $codigo }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('area')" />
                        </div>
                    </div>

                    <fieldset>
                        <legend class="block font-medium text-sm text-gray-700">Ilustración</legend>
                        <p class="text-xs text-gray-500">Siempre sin textos dentro de la imagen.</p>
                        <div class="mt-2 grid sm:grid-cols-3 gap-2">
                            @foreach ([
                                'ninguna' => 'Solo la ficha.',
                                'openai' => 'Con OpenAI. Tarda un poco más.',
                                'prompt' => 'Te damos el texto para pegarlo en ChatGPT o Gemini y luego subes la imagen.',
                            ] as $valor => $ayuda)
                                <label class="flex items-start gap-2 rounded-md border p-3 text-sm cursor-pointer {{ $ilustracion === $valor ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300' }}">
                                    <input type="radio" wire:model.live="ilustracion" value="{{ $valor }}" class="mt-0.5 border-gray-300 text-indigo-600">
                                    <span><span class="font-medium text-gray-900">{{ \App\Models\Ficha::ILUSTRACIONES[$valor] }}</span><br><span class="text-xs text-gray-500">{{ $ayuda }}</span></span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('ilustracion')" />
                    </fieldset>

                    <div class="flex items-center gap-3">
                        <x-primary-button wire:loading.attr="disabled">Crear ficha en 3 niveles</x-primary-button>
                        <span class="text-sm text-gray-500">Tarda entre 30 y 90 segundos.</span>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

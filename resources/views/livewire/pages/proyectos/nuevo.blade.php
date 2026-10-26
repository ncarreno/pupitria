<?php

use App\Jobs\GenerarProyecto;
use App\Models\Clase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $claseId = null;

    public string $idea = '';

    /** @var array<int, string> */
    public array $areas = [];

    public string $duracion = '6 semanas';

    public string $tipo = 'aula';

    public function mount(): void
    {
        $this->claseId = $this->clases->first()?->id;
        $this->areas = $this->clase?->areas->pluck('codigo')->take(4)->all() ?? [];
    }

    #[Computed]
    public function clases()
    {
        return auth()->user()->clases()->with('areas')->get();
    }

    #[Computed]
    public function clase(): ?Clase
    {
        return $this->clases->firstWhere('id', $this->claseId);
    }

    public function updatedClaseId(): void
    {
        $this->areas = $this->clase?->areas->pluck('codigo')->take(4)->all() ?? [];
    }

    public function crear(): void
    {
        $this->validate([
            'claseId' => ['required', 'integer'],
            'idea' => ['required', 'string', 'min:10', 'max:1000'],
            'areas' => ['required', 'array', 'min:1'],
            'duracion' => ['required', 'in:2 semanas,3-4 semanas,6 semanas,Un trimestre'],
            'tipo' => ['required', 'in:aula,centro'],
        ], [
            'idea.required' => 'Escribe la idea del proyecto.',
            'idea.min' => 'Cuéntalo un poco más (al menos una frase).',
            'areas.required' => 'Elige al menos un área.',
        ]);

        $clase = Clase::with('areas')->findOrFail($this->claseId);
        $this->authorize('view', $clase);

        $areas = array_values(array_intersect($this->areas, $clase->areas->pluck('codigo')->all()));
        if (! $areas) {
            $this->addError('areas', 'Elige áreas que impartas en esta clase.');

            return;
        }

        $proyecto = auth()->user()->proyectos()->create([
            'clase_id' => $clase->id,
            'tipo' => $this->tipo,
            'curso' => $clase->curso,
            'areas' => $areas,
            'idea' => $this->idea,
            'duracion' => $this->duracion,
            'estado' => 'generando',
        ]);

        GenerarProyecto::dispatch($proyecto);

        $this->redirectRoute('proyectos.ver', $proyecto, navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo proyecto</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($this->clases->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3 text-sm text-gray-700">
                    <p>Antes de crear un proyecto, configura tu clase: así el proyecto saldrá ajustado a tu curso, tus áreas y tu número de alumnos.</p>
                    <a href="{{ route('clases') }}" wire:navigate class="text-indigo-700 hover:underline">Configurar mi clase</a>
                </div>
            @else
                <form wire:submit="crear" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <div>
                        <x-input-label for="idea" value="Idea" />
                        <p class="text-xs text-gray-500">Escríbelo como se lo dirías a una compañera.</p>
                        <textarea wire:model="idea" id="idea" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Proyecto sobre el agua en Asturias, de los ríos al mar, con un producto final que puedan ver las familias"></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('idea')" />
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="claseId" value="Clase" />
                            <select wire:model.live="claseId" id="claseId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($this->clases as $cl)
                                    <option value="{{ $cl->id }}">{{ $cl->nombre }} ({{ $cl->curso }}.º)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="duracion" value="Duración" />
                            <select wire:model="duracion" id="duracion" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach (['2 semanas', '3-4 semanas', '6 semanas', 'Un trimestre'] as $d)
                                    <option>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tipo" value="Alcance" />
                            <select wire:model="tipo" id="tipo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="aula">Mi clase</option>
                                <option value="centro">Todo el centro (1.º a 6.º)</option>
                            </select>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="block font-medium text-sm text-gray-700">Áreas del proyecto</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($this->clase?->areas ?? [] as $area)
                                <label wire:key="ap-{{ $area->codigo }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm cursor-pointer {{ in_array($area->codigo, $areas) ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700' }}">
                                    <input type="checkbox" wire:model.live="areas" value="{{ $area->codigo }}" class="rounded border-gray-300 text-indigo-600">
                                    {{ $area->nombre_corto }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('areas')" />
                    </fieldset>

                    <div class="flex items-center gap-3">
                        <x-primary-button wire:loading.attr="disabled">Crear proyecto</x-primary-button>
                        <span class="text-sm text-gray-500">Tarda entre 30 y 90 segundos.</span>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

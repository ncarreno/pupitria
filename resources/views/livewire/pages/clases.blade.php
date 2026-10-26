<?php

use App\Models\Area;
use App\Models\Clase;
use Illuminate\Validation\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /** Clase que se está editando; null = formulario de nueva clase. */
    public ?int $editandoId = null;

    public bool $mostrarFormulario = false;

    public ?int $borrandoId = null;

    public string $nombre = '';

    public int $curso = 1;

    public int $alumnos = 20;

    public int $alumnos_refuerzo = 0;

    public int $alumnos_ampliacion = 0;

    public string $curso_escolar = '';

    /** @var array<int, string> Códigos de área (LCL, MAT…) */
    public array $areas = [];

    public function mount(): void
    {
        $this->curso_escolar = Clase::cursoEscolarActual();
        $this->mostrarFormulario = auth()->user()->clases()->doesntExist();
    }

    #[Computed]
    public function clases()
    {
        return auth()->user()->clases()->with('areas')->get();
    }

    #[Computed]
    public function areasDisponibles()
    {
        return Area::orderBy('orden')->get()->filter(fn (Area $a) => $a->existeEnCurso($this->curso))->values();
    }

    public function updatedCurso(): void
    {
        $validas = $this->areasDisponibles->pluck('codigo')->all();
        $this->areas = array_values(array_intersect($this->areas, $validas));
    }

    public function nueva(): void
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }

    public function editar(int $id): void
    {
        $clase = Clase::with('areas')->findOrFail($id);
        $this->authorize('update', $clase);

        $this->editandoId = $clase->id;
        $this->nombre = $clase->nombre;
        $this->curso = $clase->curso;
        $this->alumnos = $clase->alumnos;
        $this->alumnos_refuerzo = $clase->alumnos_refuerzo;
        $this->alumnos_ampliacion = $clase->alumnos_ampliacion;
        $this->curso_escolar = $clase->curso_escolar;
        $this->areas = $clase->areas->pluck('codigo')->all();
        $this->resetValidation();
        $this->mostrarFormulario = true;
    }

    public function guardar(): void
    {
        $datos = $this->withValidator(function (Validator $validator) {
            $validator->after(function (Validator $v) {
                if ($this->alumnos_refuerzo + $this->alumnos_ampliacion > $this->alumnos) {
                    $v->errors()->add('alumnos_ampliacion', 'Refuerzo y ampliación no pueden sumar más que el total de alumnos.');
                }
                $noValidas = array_diff($this->areas, $this->areasDisponibles->pluck('codigo')->all());
                if ($noValidas) {
                    $v->errors()->add('areas', 'Estas áreas no existen en '.$this->curso.'.º: '.implode(', ', $noValidas).'.');
                }
            });
        })->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'curso' => ['required', 'integer', 'between:1,6'],
            'alumnos' => ['required', 'integer', 'between:1,40'],
            'alumnos_refuerzo' => ['required', 'integer', 'min:0'],
            'alumnos_ampliacion' => ['required', 'integer', 'min:0'],
            'curso_escolar' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => ['string', 'exists:areas,codigo'],
        ], [
            'areas.required' => 'Elige al menos un área.',
            'curso_escolar.regex' => 'Escribe el curso escolar así: 2026/2027.',
        ]);

        $clase = $this->editandoId
            ? tap(Clase::findOrFail($this->editandoId), fn (Clase $c) => $this->authorize('update', $c))
            : auth()->user()->clases()->make();

        $clase->fill(collect($datos)->except('areas')->all())->save();
        $clase->areas()->sync(Area::whereIn('codigo', $datos['areas'])->pluck('id'));

        $this->resetForm();
        $this->mostrarFormulario = false;
        unset($this->clases);
        $this->dispatch('clase-guardada');
    }

    public function cancelar(): void
    {
        $this->resetForm();
        $this->mostrarFormulario = false;
    }

    public function confirmarBorrado(int $id): void
    {
        $this->borrandoId = $id;
    }

    public function borrar(): void
    {
        $clase = Clase::findOrFail($this->borrandoId);
        $this->authorize('delete', $clase);
        $clase->delete();

        $this->borrandoId = null;
        unset($this->clases);
    }

    private function resetForm(): void
    {
        $this->reset(['editandoId', 'nombre', 'curso', 'alumnos', 'alumnos_refuerzo', 'alumnos_ampliacion', 'areas']);
        $this->curso_escolar = Clase::cursoEscolarActual();
        $this->resetValidation();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis clases</h2>
            @unless ($mostrarFormulario)
                <x-primary-button wire:click="nueva">Añadir clase</x-primary-button>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($mostrarFormulario)
                <form wire:submit="guardar" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <header>
                        <h3 class="text-lg font-medium text-gray-900">{{ $editandoId ? 'Editar clase' : 'Nueva clase' }}</h3>
                        <p class="mt-1 text-sm text-gray-600">No guardamos nombres ni datos de tus alumnos: solo cuántos son y cuántos necesitan refuerzo o ampliación.</p>
                    </header>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-1">
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input wire:model="nombre" id="nombre" type="text" class="mt-1 block w-full" placeholder="3.º A" required />
                            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                        </div>
                        <div>
                            <x-input-label for="curso" value="Curso" />
                            <select wire:model.live="curso" id="curso" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (range(1, 6) as $n)
                                    <option value="{{ $n }}">{{ $n }}.º ({{ (int) ceil($n / 2) }}.º ciclo)</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('curso')" />
                        </div>
                        <div>
                            <x-input-label for="curso_escolar" value="Curso escolar" />
                            <x-text-input wire:model="curso_escolar" id="curso_escolar" type="text" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('curso_escolar')" />
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="alumnos" value="Alumnos" />
                            <x-text-input wire:model="alumnos" id="alumnos" type="number" min="1" max="40" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('alumnos')" />
                        </div>
                        <div>
                            <x-input-label for="alumnos_refuerzo" value="Necesitan refuerzo" />
                            <x-text-input wire:model="alumnos_refuerzo" id="alumnos_refuerzo" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('alumnos_refuerzo')" />
                        </div>
                        <div>
                            <x-input-label for="alumnos_ampliacion" value="Necesitan ampliación" />
                            <x-text-input wire:model="alumnos_ampliacion" id="alumnos_ampliacion" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('alumnos_ampliacion')" />
                        </div>
                    </div>

                    <fieldset>
                        <legend class="block font-medium text-sm text-gray-700">Áreas que impartes en esta clase</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($this->areasDisponibles as $area)
                                <label wire:key="area-{{ $area->codigo }}" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm cursor-pointer {{ in_array($area->codigo, $areas) ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700' }}">
                                    <input type="checkbox" wire:model.live="areas" value="{{ $area->codigo }}" class="rounded border-gray-300 text-indigo-600">
                                    {{ $area->nombre_corto }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Lengua Asturiana y Cultura Asturiana son alternativas. Valores Cívicos y Éticos solo existe en 6.º.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('areas')" />
                    </fieldset>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Guardar</x-primary-button>
                        @if ($this->clases->isNotEmpty())
                            <x-secondary-button type="button" wire:click="cancelar">Cancelar</x-secondary-button>
                        @endif
                    </div>
                </form>
            @endif

            <x-action-message on="clase-guardada" class="text-sm text-green-700">Clase guardada.</x-action-message>

            @if ($this->clases->isNotEmpty())
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach ($this->clases as $clase)
                        <article wire:key="clase-{{ $clase->id }}" class="bg-white shadow-sm sm:rounded-lg p-5 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $clase->nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ $clase->curso }}.º de Primaria · {{ $clase->ciclo() }}.º ciclo · {{ $clase->curso_escolar }}</p>
                                </div>
                                <div class="flex gap-2 text-sm">
                                    <button type="button" wire:click="editar({{ $clase->id }})" class="text-indigo-700 hover:underline">Editar</button>
                                    <button type="button" wire:click="confirmarBorrado({{ $clase->id }})" class="text-red-700 hover:underline">Borrar</button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-800">
                                {{ $clase->alumnos }} alumnos
                                · {{ $clase->alumnos_refuerzo }} con refuerzo
                                · {{ $clase->alumnos_ampliacion }} con ampliación
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($clase->areas as $area)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-700">{{ $area->nombre_corto }}</span>
                                @endforeach
                            </div>

                            @if ($borrandoId === $clase->id)
                                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800 flex flex-wrap items-center gap-3">
                                    <span>¿Borrar {{ $clase->nombre }}? Esta acción no se puede deshacer.</span>
                                    <x-danger-button type="button" wire:click="borrar">Borrar</x-danger-button>
                                    <x-secondary-button type="button" wire:click="$set('borrandoId', null)">Cancelar</x-secondary-button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @elseif (! $mostrarFormulario)
                <p class="text-sm text-gray-600">Todavía no tienes clases.</p>
            @endif
        </div>
    </div>
</div>

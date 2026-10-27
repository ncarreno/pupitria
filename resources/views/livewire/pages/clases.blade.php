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
        <x-cabecera antetitulo="Tu grupo" titulo="Mis clases">
            Los proyectos y las fichas se ajustan al curso, a las áreas y a cuántos alumnos necesitan refuerzo o ampliación.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-6">
        @if ($mostrarFormulario)
            <form wire:submit="guardar" class="tarjeta overflow-hidden animate-aparecer">
                <header class="flex flex-wrap items-start justify-between gap-4 border-b border-stone-100 px-6 py-5 sm:px-8">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight text-stone-950">{{ $editandoId ? 'Editar clase' : 'Nueva clase' }}</h3>
                        <p class="mt-1 text-sm text-stone-600">No guardamos nombres ni datos de tus alumnos: solo cuántos son y cuántos necesitan refuerzo o ampliación.</p>
                    </div>
                    <span class="insignia-pino"><x-icono nombre="escudo" class="size-3.5" />Sin datos personales</span>
                </header>

                <div class="divide-y divide-stone-100">
                    <div class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <p class="text-sm font-medium text-stone-950">La clase</p>
                            <p class="ayuda mt-1">Cómo la reconoces y en qué curso está.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid content-start gap-2">
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input wire:model="nombre" id="nombre" type="text" placeholder="3.º A" required />
                                <x-input-error :messages="$errors->get('nombre')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="curso" value="Curso" />
                                <select wire:model.live="curso" id="curso" class="campo">
                                    @foreach (range(1, 6) as $n)
                                        <option value="{{ $n }}">{{ $n }}.º ({{ (int) ceil($n / 2) }}.º ciclo)</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('curso')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="curso_escolar" value="Curso escolar" />
                                <x-text-input wire:model="curso_escolar" id="curso_escolar" type="text" class="cifra" />
                                <x-input-error :messages="$errors->get('curso_escolar')" />
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <p class="text-sm font-medium text-stone-950">El alumnado</p>
                            <p class="ayuda mt-1">Solo cifras, para ajustar los tres niveles.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid content-start gap-2">
                                <x-input-label for="alumnos" value="Alumnos" />
                                <x-text-input wire:model="alumnos" id="alumnos" type="number" min="1" max="40" class="cifra" required />
                                <x-input-error :messages="$errors->get('alumnos')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="alumnos_refuerzo" value="Necesitan refuerzo" />
                                <x-text-input wire:model="alumnos_refuerzo" id="alumnos_refuerzo" type="number" min="0" class="cifra" />
                                <x-input-error :messages="$errors->get('alumnos_refuerzo')" />
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="alumnos_ampliacion" value="Necesitan ampliación" />
                                <x-text-input wire:model="alumnos_ampliacion" id="alumnos_ampliacion" type="number" min="0" class="cifra" />
                                <x-input-error :messages="$errors->get('alumnos_ampliacion')" />
                            </div>
                        </div>
                    </div>

                    <fieldset class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-[220px_minmax(0,1fr)]">
                        <div>
                            <legend class="text-sm font-medium text-stone-950">Áreas que impartes en esta clase</legend>
                            <p class="ayuda mt-1">Lengua Asturiana y Cultura Asturiana son alternativas. Valores Cívicos y Éticos solo existe en 6.º.</p>
                        </div>
                        <div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->areasDisponibles as $area)
                                    @php $elegida = in_array($area->codigo, $areas); @endphp
                                    <label wire:key="area-{{ $area->codigo }}" @class(['chip', 'chip-activo' => $elegida, 'chip-inactivo' => ! $elegida])>
                                        <input type="checkbox" wire:model.live="areas" value="{{ $area->codigo }}" class="sr-only">
                                        <x-icono :nombre="$elegida ? 'check' : 'mas'" @class(['size-3.5', 'text-pino-700' => $elegida, 'text-stone-400' => ! $elegida]) />
                                        {{ $area->nombre_corto }}
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error class="mt-3" :messages="$errors->get('areas')" />
                        </div>
                    </fieldset>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-stone-100 bg-stone-50/60 px-6 py-4 sm:px-8">
                    @if ($this->clases->isNotEmpty())
                        <x-secondary-button type="button" wire:click="cancelar">Cancelar</x-secondary-button>
                    @endif
                    <x-primary-button wire:loading.attr="disabled" wire:target="guardar"><x-icono nombre="check" class="size-4" />Guardar</x-primary-button>
                </div>
            </form>
        @endif

        <x-action-message on="clase-guardada" class="aviso-pino">Clase guardada.</x-action-message>

        @if ($this->clases->isNotEmpty())
            @unless ($mostrarFormulario)
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-stone-500"><span class="cifra font-medium text-stone-900">{{ $this->clases->count() }}</span> {{ $this->clases->count() === 1 ? 'clase' : 'clases' }}</p>
                    <x-primary-button wire:click="nueva"><x-icono nombre="mas" class="size-4" />Añadir clase</x-primary-button>
                </div>
            @endunless
            <div class="grid gap-4 md:grid-cols-2 escalonado">
                @foreach ($this->clases as $clase)
                    @php
                        $estandar = max(0, $clase->alumnos - $clase->alumnos_refuerzo - $clase->alumnos_ampliacion);
                        $pct = fn (int $n) => $clase->alumnos ? round($n / $clase->alumnos * 100, 1) : 0;
                    @endphp
                    <article wire:key="clase-{{ $clase->id }}" class="tarjeta flex flex-col p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl bg-pino-700 text-lg font-semibold tabular-nums text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.15)]">{{ $clase->curso }}.º</span>
                                <div>
                                    <h3 class="text-lg font-semibold tracking-tight text-stone-950">{{ $clase->nombre }}</h3>
                                    <p class="text-sm text-stone-500">{{ $clase->curso }}.º de Primaria · {{ $clase->ciclo() }}.º ciclo · {{ $clase->curso_escolar }}</p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" wire:click="editar({{ $clase->id }})" class="boton-fantasma boton-sm"><x-icono nombre="lapiz" class="size-4" />Editar</button>
                                <button type="button" wire:click="confirmarBorrado({{ $clase->id }})" class="boton-fantasma boton-sm text-red-700 hover:bg-red-50 hover:text-red-800"><x-icono nombre="papelera" class="size-4" />Borrar</button>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline justify-between">
                                <p class="text-sm text-stone-800"><span class="cifra text-2xl font-medium text-stone-950">{{ $clase->alumnos }}</span> alumnos</p>
                            </div>
                            <div class="mt-3 flex h-2 gap-0.5 overflow-hidden rounded-full" aria-hidden="true">
                                @if ($clase->alumnos_refuerzo)<span class="bg-pino-300" style="width: {{ $pct($clase->alumnos_refuerzo) }}%"></span>@endif
                                @if ($estandar)<span class="bg-stone-300" style="width: {{ $pct($estandar) }}%"></span>@endif
                                @if ($clase->alumnos_ampliacion)<span class="bg-pino-700" style="width: {{ $pct($clase->alumnos_ampliacion) }}%"></span>@endif
                            </div>
                            <p class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[13px] text-stone-500">
                                <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-pino-300"></i>{{ $clase->alumnos_refuerzo }} con refuerzo</span>
                                <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-stone-300"></i>{{ $estandar }} estándar</span>
                                <span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-pino-700"></i>{{ $clase->alumnos_ampliacion }} con ampliación</span>
                            </p>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-1.5 border-t border-stone-100 pt-4">
                            @foreach ($clase->areas as $area)
                                <span class="insignia-neutra">{{ $area->nombre_corto }}</span>
                            @endforeach
                        </div>

                        @if ($borrandoId === $clase->id)
                            <div class="aviso-rojo mt-5 flex-wrap items-center animate-aparecer">
                                <x-icono nombre="aviso" class="size-5 text-red-700" />
                                <span class="flex-1">¿Borrar {{ $clase->nombre }}? Esta acción no se puede deshacer.</span>
                                <div class="flex gap-2">
                                    <x-secondary-button type="button" wire:click="$set('borrandoId', null)" class="boton-sm">Cancelar</x-secondary-button>
                                    <x-danger-button type="button" wire:click="borrar" class="boton-sm">Borrar</x-danger-button>
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @elseif (! $mostrarFormulario)
            <x-estado-vacio icono="grupo" titulo="Todavía no tienes clases.">
                Añade tu primera clase para que los proyectos salgan ajustados a tu grupo.
                <x-slot:acciones>
                    <x-primary-button wire:click="nueva"><x-icono nombre="mas" class="size-4" />Añadir clase</x-primary-button>
                </x-slot:acciones>
            </x-estado-vacio>
        @endif
    </div>
</div>

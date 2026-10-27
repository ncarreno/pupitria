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
        <x-cabecera antetitulo="Proyectos" titulo="Nuevo proyecto" :volver="route('proyectos')" volver-texto="Todos los proyectos">
            Cuéntalo en una frase. La app prepara el diseño completo con el currículo de tu ciclo.
        </x-cabecera>
    </x-slot>

    <div class="contenedor">
        @if ($this->clases->isEmpty())
            <x-estado-vacio icono="grupo" titulo="Primero, tu clase">
                Antes de crear un proyecto, configura tu clase: así el proyecto saldrá ajustado a tu curso, tus áreas y tu número de alumnos.
                <x-slot:acciones>
                    <a href="{{ route('clases') }}" wire:navigate class="boton-primario">Configurar mi clase<x-icono nombre="flecha-derecha" class="size-4" /></a>
                </x-slot:acciones>
            </x-estado-vacio>
        @else
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <form wire:submit="crear" class="tarjeta overflow-hidden animate-aparecer">
                    <div class="space-y-7 p-6 sm:p-8">
                        <div class="grid gap-2">
                            <x-input-label for="idea" value="Idea" />
                            <p class="ayuda -mt-1">Escríbelo como se lo dirías a una compañera.</p>
                            <textarea wire:model="idea" id="idea" rows="5" class="campo resize-y text-[15px] leading-relaxed" placeholder="Proyecto sobre el agua en Asturias, de los ríos al mar, con un producto final que puedan ver las familias"></textarea>
                            <x-input-error :messages="$errors->get('idea')" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="grid content-start gap-2">
                                <x-input-label for="claseId" value="Clase" />
                                <select wire:model.live="claseId" id="claseId" class="campo">
                                    @foreach ($this->clases as $cl)
                                        <option value="{{ $cl->id }}">{{ $cl->nombre }} ({{ $cl->curso }}.º)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="duracion" value="Duración" />
                                <select wire:model="duracion" id="duracion" class="campo">
                                    @foreach (['2 semanas', '3-4 semanas', '6 semanas', 'Un trimestre'] as $d)
                                        <option>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="tipo" value="Alcance" />
                                <select wire:model="tipo" id="tipo" class="campo">
                                    <option value="aula">Mi clase</option>
                                    <option value="centro">Todo el centro (1.º a 6.º)</option>
                                </select>
                            </div>
                        </div>

                        <fieldset class="grid gap-3">
                            <legend class="etiqueta">Áreas del proyecto</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->clase?->areas ?? [] as $area)
                                    @php $elegida = in_array($area->codigo, $areas); @endphp
                                    <label wire:key="ap-{{ $area->codigo }}" @class(['chip', 'chip-activo' => $elegida, 'chip-inactivo' => ! $elegida])>
                                        <input type="checkbox" wire:model.live="areas" value="{{ $area->codigo }}" class="sr-only">
                                        <x-icono :nombre="$elegida ? 'check' : 'mas'" @class(['size-3.5', 'text-pino-700' => $elegida, 'text-stone-400' => ! $elegida]) />
                                        {{ $area->nombre_corto }}
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('areas')" />
                        </fieldset>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 bg-stone-50/60 px-6 py-4 sm:px-8">
                        <span class="inline-flex items-center gap-2 text-sm text-stone-500"><x-icono nombre="reloj" class="size-4" />Tarda entre 30 y 90 segundos.</span>
                        <x-primary-button wire:loading.attr="disabled" wire:target="crear">
                            <x-icono nombre="chispa" class="size-4" wire:loading.remove wire:target="crear" />
                            <span class="punto-vivo" wire:loading wire:target="crear"></span>
                            Crear proyecto
                        </x-primary-button>
                    </div>
                </form>

                <aside class="space-y-4 animate-aparecer [animation-delay:100ms] lg:sticky lg:top-24">
                    <div class="tarjeta p-6">
                        <h2 class="tarjeta-titulo">Qué vas a recibir</h2>
                        <ul class="mt-4 space-y-3 text-sm text-stone-700">
                            @foreach ([
                                'Título, lema y narración para enganchar a la clase',
                                'Criterios y saberes de tu ciclo, comprobados con el decreto',
                                'Tareas con sus áreas, desde las que sacar fichas',
                                'Metodologías, agrupamientos, evaluación y difusión',
                            ] as $punto)
                                <li class="flex gap-2.5"><x-icono nombre="check-circulo" class="mt-px size-[18px] text-pino-600" />{{ $punto }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="rounded-2xl bg-stone-100/80 p-5 text-[13px] leading-relaxed text-stone-600 ring-1 ring-inset ring-stone-200/70">
                        <p class="flex items-center gap-2 font-medium text-stone-900"><x-icono nombre="lapiz" class="size-4 text-pino-700" />Después lo ajustas a tu gusto</p>
                        <p class="mt-1.5">Puedes editar cualquier texto, pedir cambios en una frase y volver a una versión anterior.</p>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>

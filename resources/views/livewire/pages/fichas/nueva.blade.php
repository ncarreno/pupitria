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
        if (! auth()->user()->puedeGenerar()) {
            $this->addError('limite', auth()->user()->mensajeLimite());

            return;
        }

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
        <x-cabecera antetitulo="Fichas" titulo="Nueva ficha" :volver="route('fichas')" volver-texto="Todas las fichas">
            Una ficha en tres niveles sobre el mismo contenido, para que toda la clase trabaje lo mismo a la vez.
        </x-cabecera>
    </x-slot>

    <div class="contenedor">
        @if ($this->clases->isEmpty())
            <x-estado-vacio icono="grupo" titulo="Primero, tu clase">
                Antes de crear una ficha, configura tu clase.
                <x-slot:acciones>
                    <a href="{{ route('clases') }}" wire:navigate class="boton-primario">Configurar mi clase<x-icono nombre="flecha-derecha" class="size-4" /></a>
                </x-slot:acciones>
            </x-estado-vacio>
        @else
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <form wire:submit="crear" class="tarjeta overflow-hidden animate-aparecer">
                    @if ($this->proyectoElegido)
                        <div class="flex items-center gap-3 border-b border-pino-100 bg-pino-50/70 px-6 py-3.5 text-sm text-pino-900 sm:px-8">
                            <x-icono nombre="tablero" class="size-[18px] text-pino-700" />
                            <p>Para el proyecto <span class="font-medium text-stone-950">{{ $this->proyectoElegido->titulo }}</span>@if ($tarea !== null), tarea {{ $tarea + 1 }}@endif.</p>
                        </div>
                    @endif

                    <div class="space-y-7 p-6 sm:p-8">
                        <div class="grid gap-2">
                            <x-input-label for="tema" value="Qué quieres trabajar" />
                            <p class="ayuda -mt-1">Tema concreto, tipo de actividad o lo que no quieres.</p>
                            <textarea wire:model="tema" id="tema" rows="5" class="campo resize-y text-[15px] leading-relaxed" placeholder="Animales de Oceanía y cómo se adaptan a su ecosistema. Con una lectura corta y nada de casillas vacías."></textarea>
                            <x-input-error :messages="$errors->get('tema')" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid content-start gap-2">
                                <x-input-label for="claseId" value="Clase" />
                                <select wire:model.live="claseId" id="claseId" class="campo">
                                    @foreach ($this->clases as $cl)
                                        <option value="{{ $cl->id }}">{{ $cl->nombre }} ({{ $cl->curso }}.º)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-2">
                                <x-input-label for="area" value="Área" />
                                <select wire:model="area" id="area" class="campo">
                                    @foreach ($this->areasDisponibles as $codigo => $nombre)
                                        <option value="{{ $codigo }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('area')" />
                            </div>
                        </div>

                        <fieldset class="grid gap-3">
                            <div>
                                <legend class="etiqueta">Ilustración</legend>
                                <p class="ayuda mt-1">Siempre sin textos dentro de la imagen.</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ([
                                    'ninguna' => ['Solo la ficha.', 'ficha'],
                                    'openai' => ['Con OpenAI. Tarda un poco más.', 'imagen'],
                                    'prompt' => ['Te damos el texto para pegarlo en ChatGPT o Gemini y luego subes la imagen.', 'copiar'],
                                ] as $valor => [$ayuda, $icono])
                                    @php $elegida = $ilustracion === $valor; @endphp
                                    <label @class(['opcion flex-col', 'opcion-activa' => $elegida, 'opcion-inactiva' => ! $elegida])>
                                        <input type="radio" wire:model.live="ilustracion" value="{{ $valor }}" class="sr-only">
                                        <span class="flex items-center justify-between">
                                            <span @class(['inline-flex size-9 items-center justify-center rounded-lg ring-1 ring-inset', 'bg-pino-700 text-white ring-pino-700' => $elegida, 'bg-stone-50 text-stone-500 ring-stone-200' => ! $elegida])><x-icono :nombre="$icono" class="size-[18px]" /></span>
                                            <span @class(['inline-flex size-5 items-center justify-center rounded-full ring-1 ring-inset transition', 'bg-pino-700 ring-pino-700 text-white' => $elegida, 'ring-stone-300' => ! $elegida])>@if ($elegida)<x-icono nombre="check" class="size-3" />@endif</span>
                                        </span>
                                        <span>
                                            <span class="block font-medium text-stone-950">{{ \App\Models\Ficha::ILUSTRACIONES[$valor] }}</span>
                                            <span class="mt-0.5 block text-[13px] leading-snug text-stone-500">{{ $ayuda }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('ilustracion')" />
                        </fieldset>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 bg-stone-50/60 px-6 py-4 sm:px-8">
                        <span class="inline-flex items-center gap-2 text-sm text-stone-500"><x-icono nombre="reloj" class="size-4" />Tarda entre 30 y 90 segundos.</span>
                        <x-limite-uso />
                        <x-primary-button wire:loading.attr="disabled" wire:target="crear" :disabled="! auth()->user()->puedeGenerar()">
                            <x-icono nombre="capas" class="size-4" wire:loading.remove wire:target="crear" />
                            <span class="punto-vivo" wire:loading wire:target="crear"></span>
                            Crear ficha en 3 niveles
                        </x-primary-button>
                    </div>
                </form>

                <aside class="tarjeta p-6 animate-aparecer [animation-delay:100ms] lg:sticky lg:top-24">
                    <h2 class="tarjeta-titulo">Los tres niveles</h2>
                    <p class="mt-1 text-[13px] text-stone-500">Mismo contenido, distinta exigencia.</p>
                    <ul class="mt-5 space-y-4">
                        @foreach ([
                            ['Refuerzo', 1, 'Frases sencillas, letra más grande y apoyos como banco de palabras u opciones para rodear.'],
                            ['Estándar', 2, 'El nivel del curso.'],
                            ['Ampliación', 3, 'Más razonamiento: comparar, explicar por qué y un pequeño reto abierto.'],
                        ] as [$nivel, $puntos, $detalle])
                            <li class="flex gap-3.5">
                                <span class="flex h-6 shrink-0 items-center gap-1 rounded-full bg-stone-50 px-2 ring-1 ring-inset ring-stone-200">
                                    @foreach ([1, 2, 3] as $k)
                                        <i @class(['block size-1.5 rounded-full ring-1 ring-pino-600', 'bg-pino-600' => $k <= $puntos])></i>
                                    @endforeach
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-stone-950">{{ $nivel }}</p>
                                    <p class="text-[13px] leading-snug text-stone-500">{{ $detalle }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-5 border-t border-stone-100 pt-4 text-[13px] leading-relaxed text-stone-500">En la hoja impresa el nivel solo se ve como puntos, así nadie se siente señalado.</p>
                </aside>
            </div>
        @endif
    </div>
</div>

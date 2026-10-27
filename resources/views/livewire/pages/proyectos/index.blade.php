<?php

use App\Models\Area;
use App\Models\Criterio;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    /** Filtros en la URL, para que "Atrás" y los enlaces los conserven. '' = todos. */
    #[Url]
    public string $buscar = '';

    #[Url]
    public string $clase = '';

    #[Url]
    public string $curso = '';

    #[Url]
    public string $area = '';

    #[Url]
    public string $criterio = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function quitarFiltros(): void
    {
        $this->reset('buscar', 'clase', 'curso', 'area', 'criterio');
        $this->resetPage();
    }

    #[Computed]
    public function hayFiltros(): bool
    {
        return trim($this->buscar) !== '' || $this->clase !== '' || $this->curso !== '' || $this->area !== '' || $this->criterio !== '';
    }

    /**
     * Solo en lo de la docente: todo parte de `auth()->user()->proyectos()`.
     */
    #[Computed]
    public function proyectos()
    {
        $buscar = addcslashes(trim($this->buscar), '%_\\');

        return auth()->user()->proyectos()->with('clase')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('titulo', 'like', "%{$buscar}%")
                ->orWhere('idea', 'like', "%{$buscar}%")))
            ->when($this->clase !== '', fn ($q) => $q->where('clase_id', (int) $this->clase))
            ->when($this->curso !== '', fn ($q) => $q->where('curso', (int) $this->curso))
            ->when($this->area !== '', fn ($q) => $q->whereJsonContains('areas', $this->area))
            ->when($this->criterio !== '', fn ($q) => $q->whereHas('criterios', fn ($q) => $q->whereKey($this->criterio)))
            ->paginate(20);
    }

    #[Computed]
    public function tieneProyectos(): bool
    {
        return auth()->user()->proyectos()->exists();
    }

    #[Computed]
    public function opciones(): array
    {
        $proyectos = auth()->user()->proyectos()->reorder();

        return [
            'clases' => auth()->user()->clases()->get(['id', 'nombre', 'curso_escolar']),
            'cursos' => (clone $proyectos)->distinct()->orderBy('curso')->pluck('curso'),
            'areas' => Area::whereIn('codigo', (clone $proyectos)->pluck('areas')->flatten()->unique())->orderBy('id')->pluck('nombre_corto', 'codigo'),
            'criterios' => Criterio::with('area')->whereHas('proyectos', fn ($q) => $q->where('user_id', auth()->id()))->orderBy('id')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <x-cabecera antetitulo="Tu trabajo" titulo="Proyectos">
            Cada proyecto guarda sus versiones y las fichas que saques de sus tareas.
            <x-slot:acciones>
                <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario"><x-icono nombre="mas" class="size-4" />Nuevo proyecto</a>
            </x-slot:acciones>
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5">
        @if (! $this->tieneProyectos)
            <x-estado-vacio icono="tablero" titulo="Tu primer proyecto empieza con una frase">
                Todavía no tienes proyectos. Escribe tu idea en una frase y la app prepara la ficha completa con criterios reales del currículo.
                <x-slot:acciones>
                    <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario"><x-icono nombre="chispa" class="size-4" />Crear un proyecto</a>
                </x-slot:acciones>
            </x-estado-vacio>
        @else
            <div class="flex flex-wrap items-center gap-3">
                <label class="relative block w-full sm:max-w-xs">
                    <span class="sr-only">Buscar</span>
                    <x-icono nombre="buscar" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Título o idea" class="campo ps-10">
                    <span wire:loading.delay wire:target="buscar" class="absolute right-3.5 top-1/2 -translate-y-1/2"><span class="punto-vivo text-pino-600"></span></span>
                </label>
                @if ($this->opciones['clases']->count() > 1)
                    <select wire:model.live="clase" class="campo w-auto" aria-label="Clase">
                        <option value="">Todas las clases</option>
                        @foreach ($this->opciones['clases'] as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->curso_escolar }})</option>
                        @endforeach
                    </select>
                @endif
                @if ($this->opciones['cursos']->count() > 1)
                    <select wire:model.live="curso" class="campo w-auto" aria-label="Curso">
                        <option value="">Todos los cursos</option>
                        @foreach ($this->opciones['cursos'] as $n)
                            <option value="{{ $n }}">{{ $n }}.º</option>
                        @endforeach
                    </select>
                @endif
                @if ($this->opciones['areas']->count() > 1)
                    <select wire:model.live="area" class="campo w-auto" aria-label="Área">
                        <option value="">Todas las áreas</option>
                        @foreach ($this->opciones['areas'] as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($this->opciones['criterios']->isNotEmpty())
                    <select wire:model.live="criterio" class="campo w-auto max-w-full" aria-label="Criterio">
                        <option value="">Cualquier criterio</option>
                        @foreach ($this->opciones['criterios'] as $cr)
                            <option value="{{ $cr->id }}">{{ $cr->etiqueta() }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($this->hayFiltros)
                    <button type="button" wire:click="quitarFiltros" class="enlace text-sm"><x-icono nombre="x" class="size-4" />Quitar los filtros</button>
                @endif
            </div>

            @if ($this->proyectos->isEmpty())
                <x-estado-vacio icono="buscar" titulo="Ningún proyecto con estos filtros">
                    Prueba con otras palabras o quita algún filtro.
                    <x-slot:acciones>
                        <button type="button" wire:click="quitarFiltros" class="boton-secundario">Quitar los filtros</button>
                    </x-slot:acciones>
                </x-estado-vacio>
            @else
                @if ($this->hayFiltros)
                    <p class="cifra text-[13px] text-stone-500">{{ $this->proyectos->total() }} {{ $this->proyectos->total() === 1 ? 'proyecto' : 'proyectos' }}</p>
                @endif
                <ul class="tarjeta divide-y divide-stone-100 overflow-hidden escalonado">
                    @foreach ($this->proyectos as $p)
                        <li wire:key="p-{{ $p->id }}">
                            <a href="{{ route('proyectos.ver', $p) }}" wire:navigate class="group flex items-center gap-4 px-5 py-4 transition hover:bg-stone-50/80 sm:px-6">
                                <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-stone-50 text-stone-500 ring-1 ring-inset ring-stone-200 transition group-hover:bg-pino-50 group-hover:text-pino-700 group-hover:ring-pino-200 sm:inline-flex">
                                    <x-icono nombre="tablero" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-stone-950">{{ $p->titulo ?: ($p->estaGenerando() ? 'Generando…' : str($p->idea)->limit(60)) }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-stone-500">
                                        <span>{{ $p->clase?->nombre ?? $p->curso.'.º' }}</span>
                                        <span class="text-stone-300">·</span>
                                        <span>{{ $p->tipo === 'centro' ? 'Centro' : 'Aula' }}</span>
                                        <span class="text-stone-300">·</span>
                                        <span class="cifra">{{ $p->updated_at->format('d/m/Y') }}</span>
                                    </p>
                                </div>
                                @if ($p->estado === 'generando')
                                    <span class="insignia-pino"><span class="punto-vivo size-1.5"></span>generando</span>
                                @endif
                                @if ($p->estado === 'error')
                                    <span class="insignia-rojo"><x-icono nombre="aviso" class="size-3" />error</span>
                                @endif
                                <x-icono nombre="chevron-derecha" class="size-4 text-stone-300 transition duration-300 ease-suave group-hover:translate-x-0.5 group-hover:text-stone-500" />
                            </a>
                        </li>
                    @endforeach
                </ul>
                {{ $this->proyectos->links() }}
            @endif
        @endif
    </div>
</div>

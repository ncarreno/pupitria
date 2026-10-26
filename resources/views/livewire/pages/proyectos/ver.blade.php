<?php

use App\Jobs\GenerarProyecto;
use App\Models\Proyecto;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Proyecto $proyecto;

    public string $ajuste = '';

    public bool $confirmarBorrado = false;

    /**
     * Campos del documento que la docente puede editar a mano. El currículo no se edita
     * a mano: se cambia pidiendo un ajuste, para que siempre esté validado contra el decreto.
     */
    private const RUTAS_EDITABLES = '/^(titulo|lema|temporalizacion|narracion\.(secuencia|vinculacion)|ods\.desafio|producto_final|agrupamientos|espacios|recursos|tic|tareas\.\d+\.(titulo|descripcion)|metodologias\.\d+\.(nombre|descripcion)|evaluacion\.\d+\.(instrumento|que)|difusion\.\d+|itinerario\.\d+\.(formato|elige_profesorado|aporta_alumnado|reto|duracion))$/';

    public const ATAJOS = [
        'Más generalista, para que cada docente elija',
        'Más concreto para mi clase',
        'Más manipulativo',
        'Menos texto en cada apartado',
        'Conéctalo más con Asturias',
    ];

    public function mount(Proyecto $proyecto): void
    {
        $this->authorize('view', $proyecto);
        $this->proyecto = $proyecto;
    }

    /**
     * Mientras se genera, la vista consulta el estado cada pocos segundos.
     */
    public function refrescar(): void
    {
        $this->proyecto->refresh();
    }

    public function actualizar(string $ruta, string $valor): void
    {
        $this->authorize('update', $this->proyecto);
        $this->skipRender();

        $contenido = $this->proyecto->contenido ?? [];
        if ($this->proyecto->estaGenerando() || ! preg_match(self::RUTAS_EDITABLES, $ruta) || ! is_string(data_get($contenido, $ruta))) {
            return;
        }

        $valor = str(trim($valor))->limit($ruta === 'titulo' ? 255 : 2000, '')->toString();
        data_set($contenido, $ruta, $valor);
        $this->proyecto->forceFill([
            'contenido' => $contenido,
            'titulo' => $ruta === 'titulo' ? $valor : $this->proyecto->titulo,
        ])->save();
    }

    public function ajustar(?string $instruccion = null): void
    {
        $this->authorize('update', $this->proyecto);

        $instruccion = trim($instruccion ?? $this->ajuste);
        if ($instruccion === '' || $this->proyecto->estaGenerando() || ! $this->proyecto->contenido) {
            return;
        }

        $this->validate(['ajuste' => ['max:500']]);

        $this->proyecto->forceFill(['estado' => 'generando', 'error' => null])->save();
        GenerarProyecto::dispatch($this->proyecto, str($instruccion)->limit(500, '')->toString());
        $this->ajuste = '';
        $this->proyecto->refresh();
    }

    public function reintentar(): void
    {
        $this->authorize('update', $this->proyecto);

        if ($this->proyecto->estado !== 'error') {
            return;
        }

        $this->proyecto->forceFill(['estado' => 'generando', 'error' => null])->save();
        GenerarProyecto::dispatch($this->proyecto);
        $this->proyecto->refresh();
    }

    public function restaurar(int $versionId): void
    {
        $this->authorize('update', $this->proyecto);

        if ($this->proyecto->estaGenerando()) {
            return;
        }

        $version = $this->proyecto->versiones()->findOrFail($versionId);
        $this->proyecto->guardarVersion($version->contenido, "Restaurar versión {$version->numero}", $version->proveedor, $version->modelo);
        $this->proyecto->refresh();
    }

    public function borrar(): void
    {
        $this->authorize('delete', $this->proyecto);
        $this->proyecto->delete();

        $this->redirectRoute('proyectos', navigate: true);
    }
}; ?>

<div @if ($proyecto->estaGenerando()) wire:poll.3s="refrescar" @endif>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $proyecto->titulo ?: 'Nuevo proyecto' }}</h2>
            <a href="{{ route('proyectos') }}" wire:navigate class="text-sm text-indigo-700 hover:underline">Todos los proyectos</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($proyecto->estaGenerando())
                <div class="bg-indigo-50 text-indigo-900 sm:rounded-lg p-4 text-sm flex items-center gap-3">
                    <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"/></svg>
                    {{ $proyecto->contenido ? 'Aplicando el cambio…' : 'Preparando el proyecto con el currículo de tu ciclo…' }} Suele tardar entre 30 y 90 segundos.
                </div>
            @endif

            @if ($proyecto->error)
                <div class="bg-amber-50 text-amber-900 sm:rounded-lg p-4 text-sm flex flex-wrap items-center gap-3">
                    <span>{{ $proyecto->error }}</span>
                    @if ($proyecto->estado === 'error')
                        <x-secondary-button wire:click="reintentar">Volver a intentarlo</x-secondary-button>
                    @endif
                </div>
            @endif

            @if ($proyecto->contenido)
                <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this::ATAJOS as $atajo)
                            <button type="button" wire:click="ajustar(@js($atajo))" @disabled($proyecto->estaGenerando()) class="rounded-full border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-50 disabled:opacity-50">{{ $atajo }}</button>
                        @endforeach
                    </div>
                    <form wire:submit="ajustar" class="flex gap-2">
                        <x-text-input wire:model="ajuste" type="text" class="flex-1" placeholder="Cambia la tarea 4 por algo más manipulativo" :disabled="$proyecto->estaGenerando()" />
                        <x-primary-button :disabled="$proyecto->estaGenerando()">Aplicar</x-primary-button>
                    </form>
                    <x-input-error :messages="$errors->get('ajuste')" />
                </div>

                <div class="overflow-x-auto shadow-sm sm:rounded-lg">
                    <x-proyecto.documento :proyecto="$proyecto" :editable="! $proyecto->estaGenerando()" wire:key="doc-{{ $proyecto->updated_at?->timestamp }}" />
                </div>
                <p class="text-xs text-gray-500">Haz clic en cualquier texto para editarlo; se guarda al salir del campo. Los criterios y saberes se cambian pidiendo un ajuste, para que siempre salgan del decreto.</p>

                <section class="bg-white shadow-sm sm:rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 text-sm mb-2">Fichas de este proyecto</h3>
                    @php($fichasPorTarea = $proyecto->fichas()->get()->groupBy('tarea_indice'))
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($proyecto->contenido['tareas'] ?? [] as $i => $t)
                            <li wire:key="ft-{{ $i }}" class="py-2 flex flex-wrap items-baseline justify-between gap-2">
                                <span class="text-gray-800"><span class="font-medium">{{ $i + 1 }}.</span> {{ $t['titulo'] }}
                                    @foreach ($fichasPorTarea[$i] ?? [] as $f)
                                        · <a href="{{ route('fichas.ver', $f) }}" wire:navigate class="text-indigo-700 hover:underline">{{ $f->titulo ?: 'ficha' }}</a>
                                    @endforeach
                                </span>
                                <a href="{{ route('fichas.nueva', ['proyecto' => $proyecto->id, 'tarea' => $i]) }}" wire:navigate class="text-indigo-700 hover:underline shrink-0">Crear ficha</a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <div class="grid md:grid-cols-2 gap-4">
                    <section class="bg-white shadow-sm sm:rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 text-sm mb-2">Versiones</h3>
                        <ul class="divide-y divide-gray-100 text-sm">
                            @foreach ($proyecto->versiones as $v)
                                <li wire:key="v-{{ $v->id }}" class="py-2 flex items-baseline justify-between gap-3">
                                    <span class="text-gray-700">
                                        <span class="font-medium">v{{ $v->numero }}</span>
                                        · {{ $v->instruccion ?? 'Generación inicial' }}
                                        <span class="text-gray-400">· {{ $v->created_at->format('d/m H:i') }}</span>
                                    </span>
                                    @unless ($loop->first)
                                        <button type="button" wire:click="restaurar({{ $v->id }})" @disabled($proyecto->estaGenerando()) class="text-indigo-700 hover:underline shrink-0">Restaurar</button>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </section>
                    <section class="bg-white shadow-sm sm:rounded-lg p-4 text-sm space-y-2">
                        <h3 class="font-semibold text-gray-900">Datos del proyecto</h3>
                        <p class="text-gray-700"><span class="font-medium">Idea inicial:</span> {{ $proyecto->idea }}</p>
                        <p class="text-gray-700"><span class="font-medium">Clase:</span> {{ $proyecto->clase?->nombre ?? '—' }} · {{ $proyecto->duracion }}</p>
                        @if ($confirmarBorrado)
                            <div class="rounded-md bg-red-50 p-3 text-red-800 flex flex-wrap items-center gap-3">
                                <span>¿Borrar este proyecto y todas sus versiones?</span>
                                <x-danger-button wire:click="borrar">Borrar</x-danger-button>
                                <x-secondary-button wire:click="$set('confirmarBorrado', false)">Cancelar</x-secondary-button>
                            </div>
                        @else
                            <button type="button" wire:click="$set('confirmarBorrado', true)" class="text-red-700 hover:underline">Borrar proyecto</button>
                        @endif
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>

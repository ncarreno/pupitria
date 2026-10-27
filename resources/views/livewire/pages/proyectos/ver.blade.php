<?php

use App\Jobs\GenerarProyecto;
use App\Models\Proyecto;
use App\View\EstilosDocumento;
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

    /**
     * '' vuelve al estilo del perfil de la docente.
     */
    public function cambiarEstilo(string $estilo): void
    {
        $this->authorize('update', $this->proyecto);

        if ($estilo === '' || EstilosDocumento::existe($estilo)) {
            $this->proyecto->forceFill(['estilo' => $estilo ?: null])->save();
        }
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
    {{-- Cabecera dentro del componente: el título llega con el polling al terminar de generar. --}}
    <div class="contenedor pb-8 pt-10 sm:pt-12">
        <x-cabecera :antetitulo="$proyecto->tipo === 'centro' ? 'Proyecto de centro' : 'Proyecto de aula'" :titulo="$proyecto->titulo ?: 'Nuevo proyecto'" :volver="route('proyectos')" volver-texto="Todos los proyectos">
            {{ $proyecto->clase?->nombre ?? $proyecto->curso.'.º' }} · {{ $proyecto->duracion }}
        </x-cabecera>
    </div>

    <div class="contenedor space-y-5">
        @if ($proyecto->estaGenerando())
            <x-generando :esqueleto="! $proyecto->contenido">
                {{ $proyecto->contenido ? 'Aplicando el cambio…' : 'Preparando el proyecto con el currículo de tu ciclo…' }}
            </x-generando>
        @endif

        @if ($proyecto->error)
            <div class="aviso-ambar flex-wrap items-center">
                <x-icono nombre="aviso" class="size-5 text-amber-700" />
                <span class="flex-1">{{ $proyecto->error }}</span>
                @if ($proyecto->estado === 'error')
                    <x-secondary-button wire:click="reintentar" class="boton-sm"><x-icono nombre="refrescar" class="size-4" />Volver a intentarlo</x-secondary-button>
                @endif
            </div>
        @endif

        @if ($proyecto->contenido)
            <section class="tarjeta p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="flex items-center gap-3 lg:w-56 lg:shrink-0">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="chispa" class="size-[18px]" /></span>
                        <div>
                            <p class="text-sm font-semibold text-stone-950">Pide un cambio</p>
                            <p class="text-xs text-stone-500">Se guarda como versión nueva</p>
                        </div>
                    </div>
                    <form wire:submit="ajustar" class="flex flex-1 gap-2">
                        <x-text-input wire:model="ajuste" type="text" class="flex-1" placeholder="Cambia la tarea 4 por algo más manipulativo" :disabled="$proyecto->estaGenerando()" />
                        <x-primary-button :disabled="$proyecto->estaGenerando()">Aplicar</x-primary-button>
                    </form>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 lg:ps-[15.5rem]">
                    @foreach ($this::ATAJOS as $atajo)
                        <button type="button" wire:click="ajustar(@js($atajo))" @disabled($proyecto->estaGenerando()) class="chip chip-inactivo py-1 text-[13px] disabled:pointer-events-none disabled:opacity-50">{{ $atajo }}</button>
                    @endforeach
                </div>
                <x-input-error class="mt-3" :messages="$errors->get('ajuste')" />
            </section>

            <div>
                <div class="mb-3 flex justify-end">
                    <x-selector-estilo :actual="$proyecto->estilo" />
                </div>
                <div class="overflow-x-auto rounded-2xl shadow-papel ring-1 ring-stone-200">
                    <x-proyecto.documento :proyecto="$proyecto" :editable="! $proyecto->estaGenerando()" wire:key="doc-{{ $proyecto->updated_at?->timestamp }}" />
                </div>
                <p class="mt-3 flex items-start gap-2 text-[13px] text-stone-500"><x-icono nombre="lapiz" class="mt-px size-4 text-stone-400" />Haz clic en cualquier texto para editarlo; se guarda al salir del campo. Los criterios y saberes se cambian pidiendo un ajuste, para que siempre salgan del decreto.</p>
            </div>

            <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <section class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Fichas de este proyecto</h3>
                        <span class="text-xs text-stone-500">Una o más por tarea</span>
                    </div>
                    @php $fichasPorTarea = $proyecto->fichas()->get()->groupBy('tarea_indice'); @endphp
                    <ul class="divide-y divide-stone-100">
                        @foreach ($proyecto->contenido['tareas'] ?? [] as $i => $t)
                            <li wire:key="ft-{{ $i }}" class="flex items-start gap-4 px-5 py-4 sm:px-6">
                                <span class="cifra inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-pino-700 text-xs font-medium text-white">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-stone-900">{{ $t['titulo'] }}</p>
                                    @if ($fichasPorTarea[$i] ?? null)
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($fichasPorTarea[$i] as $f)
                                                <a href="{{ route('fichas.ver', $f) }}" wire:navigate class="inline-flex max-w-full items-center gap-1.5 rounded-lg bg-stone-50 px-2 py-1 text-xs font-medium text-stone-700 ring-1 ring-inset ring-stone-200 transition hover:bg-pino-50 hover:text-pino-800 hover:ring-pino-200">
                                                    <x-icono nombre="ficha" class="size-3.5" /><span class="truncate">{{ $f->titulo ?: 'ficha' }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('fichas.nueva', ['proyecto' => $proyecto->id, 'tarea' => $i]) }}" wire:navigate class="boton-secundario boton-sm shrink-0"><x-icono nombre="mas" class="size-3.5" />Crear ficha</a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <div class="space-y-5">
                    <section class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Versiones</h3>
                            <x-icono nombre="historial" class="size-4 text-stone-400" />
                        </div>
                        <ol class="relative px-5 py-4 sm:px-6">
                            <span aria-hidden="true" class="absolute bottom-6 left-[1.9rem] top-6 w-px bg-stone-200 sm:left-[2.15rem]"></span>
                            @foreach ($proyecto->versiones as $v)
                                <li wire:key="v-{{ $v->id }}" class="relative flex items-start gap-3 py-2">
                                    <span @class(['relative mt-1 size-2.5 shrink-0 rounded-full ring-4 ring-white', 'bg-pino-600' => $loop->first, 'bg-stone-300' => ! $loop->first])></span>
                                    <div class="min-w-0 flex-1 text-sm">
                                        <p class="text-stone-800"><span class="cifra font-medium text-stone-950">v{{ $v->numero }}</span> · {{ $v->instruccion ?? 'Generación inicial' }}</p>
                                        <p class="cifra text-xs text-stone-400">{{ $v->created_at->format('d/m H:i') }}</p>
                                    </div>
                                    @unless ($loop->first)
                                        <button type="button" wire:click="restaurar({{ $v->id }})" @disabled($proyecto->estaGenerando()) class="enlace shrink-0 text-[13px]">Restaurar</button>
                                    @else
                                        <span class="insignia-pino shrink-0">actual</span>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <section class="tarjeta p-5 text-sm sm:p-6">
                        <h3 class="tarjeta-titulo">Datos del proyecto</h3>
                        <dl class="mt-4 space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-stone-500">Idea inicial:</dt>
                                <dd class="mt-0.5 leading-relaxed text-stone-800">{{ $proyecto->idea }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-stone-500">Clase:</dt>
                                <dd class="mt-0.5 text-stone-800">{{ $proyecto->clase?->nombre ?? '—' }} · {{ $proyecto->duracion }}</dd>
                            </div>
                        </dl>
                        <div class="mt-5 border-t border-stone-100 pt-4">
                            @if ($confirmarBorrado)
                                <div class="aviso-rojo flex-col animate-aparecer">
                                    <span>¿Borrar este proyecto y todas sus versiones?</span>
                                    <div class="flex gap-2">
                                        <x-secondary-button wire:click="$set('confirmarBorrado', false)" class="boton-sm">Cancelar</x-secondary-button>
                                        <x-danger-button wire:click="borrar" class="boton-sm">Borrar</x-danger-button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="$set('confirmarBorrado', true)" class="enlace-peligro text-sm"><x-icono nombre="papelera" class="size-4" />Borrar proyecto</button>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        @endif
    </div>
</div>

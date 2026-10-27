<?php

use App\Models\Ficha;
use App\Models\IaCaso;
use App\Models\IaEjecucion;
use App\Services\IA\BateriaPruebas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Comparación a ciegas de un caso: la última ejecución de cada proveedor, como A, B, C…, con los
 * mismos componentes que ve la docente. Los proveedores se muestran cuando quien mira ha valorado todas.
 */
new #[Layout('layouts.admin')] class extends Component
{
    public IaCaso $caso;

    public int $opcion = 0;

    public string $nivel = 'estandar';

    /** @var array<int, int|string|null> Puntuación de 1 a 5 por id de ejecución. */
    public array $puntuaciones = [];

    /** @var array<int, string|null> */
    public array $notas = [];

    /**
     * La ruta ya pasa por `can:valorar-pruebas`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(Gate::allows('valorar-pruebas'), 403);
    }

    public function mount(IaCaso $caso): void
    {
        $this->caso = $caso;

        foreach ($this->ejecuciones as $e) {
            $mia = $e->valoracionDe(auth()->user());
            $this->puntuaciones[$e->id] = $mia?->puntuacion;
            $this->notas[$e->id] = $mia?->nota;
        }
    }

    /**
     * El orden se baraja de forma estable para cada persona, para que la posición no delate al proveedor.
     *
     * @return Collection<int, IaEjecucion>
     */
    #[Computed]
    public function ejecuciones(): Collection
    {
        return $this->caso->ultimasEjecuciones()
            ->sortBy(fn (IaEjecucion $e) => crc32($e->id.'-'.auth()->id()))
            ->values();
    }

    /**
     * A ciegas mientras quede alguna generando o alguna completada sin valorar por quien mira.
     */
    #[Computed]
    public function aCiegas(): bool
    {
        return $this->ejecuciones->contains(fn (IaEjecucion $e) => $e->estaPendiente()
            || ($e->estado === 'completada' && ! $e->valoracionDe(auth()->user())));
    }

    public function verOpcion(int $indice): void
    {
        if (isset($this->ejecuciones[$indice])) {
            $this->opcion = $indice;
        }
    }

    public function verNivel(string $nivel): void
    {
        if (array_key_exists($nivel, Ficha::NIVELES)) {
            $this->nivel = $nivel;
        }
    }

    public function valorar(int $id): void
    {
        $ejecucion = $this->ejecuciones->firstWhere('id', $id);
        abort_unless($ejecucion && $ejecucion->estado === 'completada', 404);

        $this->validate([
            "puntuaciones.{$id}" => ['required', 'integer', 'between:1,5'],
            "notas.{$id}" => ['nullable', 'string', 'max:1000'],
        ], ["puntuaciones.{$id}.required" => 'Elige una puntuación de 1 a 5.'], ["puntuaciones.{$id}" => 'puntuación', "notas.{$id}" => 'nota']);

        $ejecucion->valoraciones()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['puntuacion' => (int) $this->puntuaciones[$id], 'nota' => trim((string) ($this->notas[$id] ?? '')) ?: null],
        );

        unset($this->ejecuciones, $this->aCiegas);
        $this->dispatch('valoracion-guardada');
    }

    public function letra(int $indice): string
    {
        return chr(65 + $indice);
    }
}; ?>

<div @if ($this->ejecuciones->contains->estaPendiente()) wire:poll.5s @endif>
    @php
        $bateria = app(\App\Services\IA\BateriaPruebas::class);
        $actual = $this->ejecuciones[$opcion] ?? null;
    @endphp

    {{-- Cabecera dentro del componente: el aviso de "a ciegas" cambia al valorar. --}}
    <div class="contenedor pb-8 pt-8 lg:pt-12">
        <x-cabecera antetitulo="Batería de pruebas" :titulo="$caso->nombre" :volver="route('admin.pruebas')" volver-texto="Todos los casos">
            <span class="inline-flex items-center gap-2">
                <x-icono :nombre="$this->aCiegas ? 'ojo-tachado' : 'ojo'" class="size-4 text-pino-700" />
                @if ($this->aCiegas)
                    A ciegas: los proveedores se muestran cuando hayas valorado todas las opciones.
                @else
                    Ya has valorado todas las opciones: se muestran los proveedores.
                @endif
            </span>
        </x-cabecera>
    </div>

    <div class="contenedor space-y-5">
        <section class="tarjeta flex gap-3 p-5 text-sm text-stone-700">
            <x-icono nombre="chispa" class="mt-0.5 size-[18px] text-pino-700" />
            <p>
                <span class="font-medium text-stone-950">Lo que se pide:</span>
                {{ $caso->entrada['idea'] ?? $caso->entrada['tema'] ?? '' }}
                · {{ $caso->entrada['curso'] }}.º
                @if ($caso->tarea === 'proyecto') · {{ $caso->entrada['duracion'] ?? '' }} · {{ ($caso->entrada['tipo'] ?? 'aula') === 'centro' ? 'Centro' : 'Aula' }} @endif
            </p>
        </section>

        @if ($this->ejecuciones->isEmpty())
            <x-estado-vacio icono="matraz" titulo="Sin resultados">Este caso todavía no se ha lanzado.</x-estado-vacio>
        @else
            <div class="tarjeta overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Opción</th>
                            @unless ($this->aCiegas)<th>Proveedor y modelo</th>@endunless
                            <th>Estado</th>
                            <th class="!text-right">Duración</th>
                            <th class="!text-right">Tokens</th>
                            <th class="!text-right">Ids descartados</th>
                            <th>{{ $this->aCiegas ? 'Tu valoración' : 'Valoraciones' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->ejecuciones as $i => $e)
                            <tr wire:key="fila-{{ $e->id }}" @class(['!bg-pino-50/60' => $i === $opcion])>
                                <td>
                                    <button type="button" wire:click="verOpcion({{ $i }})" @class([
                                        'inline-flex size-8 items-center justify-center rounded-lg text-sm font-semibold transition',
                                        'bg-pino-700 text-white shadow-sm' => $i === $opcion,
                                        'bg-white text-stone-700 ring-1 ring-stone-300 hover:ring-pino-600 hover:text-pino-800' => $i !== $opcion,
                                    ])>{{ $this->letra($i) }}</button>
                                </td>
                                @unless ($this->aCiegas)<td>{{ $e->proveedor }} <span class="font-mono text-xs text-stone-500">{{ $e->generacion?->modelo ?? $e->modelo }}</span></td>@endunless
                                <td>
                                    <span @class([
                                        'insignia',
                                        'bg-pino-50 text-pino-800 ring-pino-200' => $e->estaPendiente() || $e->estado === 'completada',
                                        'bg-red-50 text-red-700 ring-red-200' => $e->estado === 'fallida',
                                    ])>
                                        @if ($e->estaPendiente())<span class="punto-vivo size-1.5"></span>@endif
                                        {{ ['pendiente' => 'generando', 'completada' => 'lista', 'fallida' => 'fallida'][$e->estado] ?? $e->estado }}
                                    </span>
                                </td>
                                <td class="cifra whitespace-nowrap text-right">{{ $e->generacion?->duracion_ms ? number_format($e->generacion->duracion_ms / 1000, 1, ',', '.').' s' : '—' }}</td>
                                <td class="cifra whitespace-nowrap text-right">{{ $e->generacion ? number_format((int) $e->generacion->tokens_entrada, 0, ',', '.').' / '.number_format((int) $e->generacion->tokens_salida, 0, ',', '.') : '—' }}</td>
                                <td class="cifra text-right">{{ $e->generacion?->ids_descartados ?? '—' }}</td>
                                <td>
                                    @if ($this->aCiegas)
                                        <span class="cifra">{{ $e->valoracionDe(auth()->user())?->puntuacion ?? '—' }}</span>
                                    @else
                                        {{ $e->valoraciones->map(fn ($v) => $v->puntuacion.' ('.$v->user->name.')')->implode(' · ') ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($actual)
                <section wire:key="opcion-{{ $actual->id }}" class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="flex items-center gap-3 text-lg font-semibold tracking-tight text-stone-950">
                            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-stone-900 text-sm text-white">{{ $this->letra($opcion) }}</span>
                            Opción {{ $this->letra($opcion) }}
                        </h3>
                        @if ($caso->tarea === 'ficha' && $actual->estado === 'completada')
                            <div class="segmentado" role="group" aria-label="Nivel">
                                @foreach (\App\Models\Ficha::NIVELES as $clave => $nombre)
                                    <button type="button" wire:click="verNivel('{{ $clave }}')" aria-pressed="{{ $nivel === $clave ? 'true' : 'false' }}"
                                        @class(['segmento', 'segmento-activo' => $nivel === $clave])>{{ $nombre }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($actual->estaPendiente())
                        <div class="aviso-pino items-center"><span class="punto-vivo text-pino-600"></span>Generando… Esta página se actualiza sola.</div>
                    @elseif ($actual->estado === 'fallida')
                        <div class="aviso-rojo">
                            <x-icono nombre="aviso" class="size-5 text-red-700" />
                            <p>
                                Esta opción ha fallado.
                                @unless ($this->aCiegas) {{ $actual->error }} @endunless
                            </p>
                        </div>
                    @else
                        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
                            <div class="min-w-0 rounded-[1.75rem] bg-stone-200/50 p-3 ring-1 ring-inset ring-stone-200 sm:p-6 xl:order-1">
                                <div class="overflow-x-auto rounded-sm shadow-papel">
                                    @if ($caso->tarea === 'ficha')
                                        <x-ficha.hoja :ficha="$bateria->ficha($caso, $actual->resultado)" :nivel="$nivel" wire:key="hoja-{{ $actual->id }}-{{ $nivel }}" />
                                    @else
                                        <x-proyecto.documento :proyecto="$bateria->proyecto($caso, $actual->resultado, (int) $actual->generacion?->ids_descartados)" wire:key="doc-{{ $actual->id }}" />
                                    @endif
                                </div>
                            </div>

                            <form wire:submit="valorar({{ $actual->id }})" class="tarjeta space-y-5 p-5 xl:sticky xl:top-6 xl:order-2">
                                <fieldset>
                                    <legend class="text-sm font-semibold text-stone-950">Tu valoración de la opción {{ $this->letra($opcion) }}</legend>
                                    <div class="mt-3 grid grid-cols-5 gap-1.5">
                                        @foreach (range(1, 5) as $n)
                                            @php $elegida = (int) ($puntuaciones[$actual->id] ?? 0) === $n; @endphp
                                            <label @class([
                                                'cifra flex h-11 cursor-pointer items-center justify-center rounded-xl text-base font-medium ring-1 ring-inset transition duration-200 ease-suave has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-pino-600 active:scale-95',
                                                'bg-pino-700 text-white ring-pino-700 shadow-sm' => $elegida,
                                                'bg-white text-stone-700 ring-stone-300 hover:ring-stone-400' => ! $elegida,
                                            ])>
                                                <input type="radio" wire:model.live="puntuaciones.{{ $actual->id }}" value="{{ $n }}" class="sr-only">
                                                {{ $n }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 flex justify-between text-xs text-stone-500"><span>1 = no la usaría</span><span>5 = la usaría tal cual</span></p>
                                    <x-input-error class="mt-2" :messages="$errors->get('puntuaciones.'.$actual->id)" />
                                </fieldset>
                                <div class="grid gap-2">
                                    <x-input-label :for="'nota-'.$actual->id" value="Nota (opcional)" />
                                    <textarea wire:model="notas.{{ $actual->id }}" id="nota-{{ $actual->id }}" rows="3" class="campo" placeholder="Qué te ha gustado o qué cambiarías"></textarea>
                                    <x-input-error :messages="$errors->get('notas.'.$actual->id)" />
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-primary-button class="flex-1">Guardar valoración</x-primary-button>
                                    <x-action-message on="valoracion-guardada">Guardada.</x-action-message>
                                </div>
                            </form>
                        </div>
                    @endif
                </section>
            @endif
        @endif
    </div>
</div>

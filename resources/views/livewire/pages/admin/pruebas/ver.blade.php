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
new #[Layout('layouts.app')] class extends Component
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

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $caso->nombre }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if ($this->aCiegas)
                        A ciegas: los proveedores se muestran cuando hayas valorado todas las opciones.
                    @else
                        Ya has valorado todas las opciones: se muestran los proveedores.
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.pruebas') }}" wire:navigate class="text-sm text-indigo-700 hover:underline">Todos los casos</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <section class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-700">
                <span class="font-medium">Lo que se pide:</span>
                {{ $caso->entrada['idea'] ?? $caso->entrada['tema'] ?? '' }}
                · {{ $caso->entrada['curso'] }}.º
                @if ($caso->tarea === 'proyecto') · {{ $caso->entrada['duracion'] ?? '' }} · {{ ($caso->entrada['tipo'] ?? 'aula') === 'centro' ? 'Centro' : 'Aula' }} @endif
            </section>

            @if ($this->ejecuciones->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">Este caso todavía no se ha lanzado.</div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Opción</th>
                                @unless ($this->aCiegas)<th class="px-4 py-2">Proveedor y modelo</th>@endunless
                                <th class="px-4 py-2">Estado</th>
                                <th class="px-4 py-2 text-right">Duración</th>
                                <th class="px-4 py-2 text-right">Tokens</th>
                                <th class="px-4 py-2 text-right">Ids descartados</th>
                                <th class="px-4 py-2">{{ $this->aCiegas ? 'Tu valoración' : 'Valoraciones' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($this->ejecuciones as $i => $e)
                                <tr wire:key="fila-{{ $e->id }}" @class(['bg-indigo-50' => $i === $opcion])>
                                    <td class="px-4 py-2"><button type="button" wire:click="verOpcion({{ $i }})" class="font-semibold text-indigo-700 hover:underline">{{ $this->letra($i) }}</button></td>
                                    @unless ($this->aCiegas)<td class="px-4 py-2">{{ $e->proveedor }} <span class="text-gray-500">{{ $e->generacion?->modelo ?? $e->modelo }}</span></td>@endunless
                                    <td class="px-4 py-2">
                                        <span @class(['text-indigo-700' => $e->estaPendiente(), 'text-red-700' => $e->estado === 'fallida'])>{{ ['pendiente' => 'generando', 'completada' => 'lista', 'fallida' => 'fallida'][$e->estado] ?? $e->estado }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">{{ $e->generacion?->duracion_ms ? number_format($e->generacion->duracion_ms / 1000, 1, ',', '.').' s' : '—' }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">{{ $e->generacion ? number_format((int) $e->generacion->tokens_entrada, 0, ',', '.').' / '.number_format((int) $e->generacion->tokens_salida, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $e->generacion?->ids_descartados ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if ($this->aCiegas)
                                            {{ $e->valoracionDe(auth()->user())?->puntuacion ?? '—' }}
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
                    <section wire:key="opcion-{{ $actual->id }}" class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="font-semibold text-gray-900">Opción {{ $this->letra($opcion) }}</h3>
                            @if ($caso->tarea === 'ficha' && $actual->estado === 'completada')
                                <div class="inline-flex rounded-md border border-gray-300 bg-white overflow-hidden" role="group" aria-label="Nivel">
                                    @foreach (\App\Models\Ficha::NIVELES as $clave => $nombre)
                                        <button type="button" wire:click="verNivel('{{ $clave }}')" aria-pressed="{{ $nivel === $clave ? 'true' : 'false' }}"
                                            class="px-4 py-2 text-sm {{ $nivel === $clave ? 'bg-gray-800 text-white font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">{{ $nombre }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($actual->estaPendiente())
                            <div class="bg-indigo-50 text-indigo-900 sm:rounded-lg p-4 text-sm">Generando… Esta página se actualiza sola.</div>
                        @elseif ($actual->estado === 'fallida')
                            <div class="bg-red-50 text-red-800 sm:rounded-lg p-4 text-sm">
                                Esta opción ha fallado.
                                @unless ($this->aCiegas) {{ $actual->error }} @endunless
                            </div>
                        @else
                            <form wire:submit="valorar({{ $actual->id }})" class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">
                                <fieldset>
                                    <legend class="text-sm font-medium text-gray-700">Tu valoración de la opción {{ $this->letra($opcion) }}</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach (range(1, 5) as $n)
                                            <label class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm cursor-pointer {{ (int) ($puntuaciones[$actual->id] ?? 0) === $n ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700' }}">
                                                <input type="radio" wire:model.live="puntuaciones.{{ $actual->id }}" value="{{ $n }}" class="border-gray-300 text-indigo-600">
                                                {{ $n }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">1 = no la usaría · 5 = la usaría tal cual.</p>
                                    <x-input-error :messages="$errors->get('puntuaciones.'.$actual->id)" />
                                </fieldset>
                                <div>
                                    <x-input-label :for="'nota-'.$actual->id" value="Nota (opcional)" />
                                    <textarea wire:model="notas.{{ $actual->id }}" id="nota-{{ $actual->id }}" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Qué te ha gustado o qué cambiarías"></textarea>
                                    <x-input-error :messages="$errors->get('notas.'.$actual->id)" />
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-primary-button>Guardar valoración</x-primary-button>
                                    <x-action-message on="valoracion-guardada" class="text-sm text-green-700">Guardada.</x-action-message>
                                </div>
                            </form>

                            <div class="overflow-x-auto shadow-sm sm:rounded-lg">
                                @if ($caso->tarea === 'ficha')
                                    <x-ficha.hoja :ficha="$bateria->ficha($caso, $actual->resultado)" :nivel="$nivel" wire:key="hoja-{{ $actual->id }}-{{ $nivel }}" />
                                @else
                                    <x-proyecto.documento :proyecto="$bateria->proyecto($caso, $actual->resultado, (int) $actual->generacion?->ids_descartados)" wire:key="doc-{{ $actual->id }}" />
                                @endif
                            </div>
                        @endif
                    </section>
                @endif
            @endif
        </div>
    </div>
</div>

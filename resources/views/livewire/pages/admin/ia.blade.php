<?php

use App\Models\IaConfiguracion;
use App\Services\IA\ClienteIA;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Panel del administrador: qué proveedor y modelo usa cada tarea, en qué orden (principal y
 * respaldos), con qué tiempo máximo, y un botón para probar cada fila con una llamada corta.
 */
new #[Layout('layouts.app')] class extends Component
{
    /** @var array<int, string> Modelo por id de fila; vacío = el modelo por defecto del proveedor. */
    public array $modelos = [];

    /** @var array<int, int|string> */
    public array $timeouts = [];

    /** @var array<string, string> Proveedor elegido para añadir, por tarea. */
    public array $nuevo = [];

    /** @var array{id: int, ok: bool, texto: string, url: ?string}|null */
    public ?array $prueba = null;

    /**
     * La ruta ya pasa por el middleware `admin`; esto lo comprueba también en cada acción de Livewire.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->esAdministrador(), 403);
    }

    public function mount(): void
    {
        $this->cargarFormulario();
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, IaConfiguracion>>
     */
    #[Computed]
    public function configuraciones()
    {
        return IaConfiguracion::orderBy('orden')->orderBy('id')->get()->groupBy('tarea');
    }

    public function guardar(int $id): void
    {
        $fila = IaConfiguracion::findOrFail($id);

        $this->validate([
            "modelos.{$id}" => ['nullable', 'string', 'max:100'],
            "timeouts.{$id}" => ['required', 'integer', 'min:10', 'max:'.IaConfiguracion::timeoutMaximo($fila->tarea)],
        ], [], ["modelos.{$id}" => 'modelo', "timeouts.{$id}" => 'tiempo máximo']);

        $fila->update([
            'modelo' => trim((string) ($this->modelos[$id] ?? '')) ?: null,
            'timeout' => (int) $this->timeouts[$id],
        ]);

        $this->dispatch('ia-guardada');
    }

    public function anadir(string $tarea): void
    {
        $proveedor = $this->nuevo[$tarea] ?? '';

        if (! array_key_exists($tarea, IaConfiguracion::TAREAS) || ! array_key_exists($proveedor, IaConfiguracion::proveedoresPara($tarea))) {
            $this->addError("nuevo.{$tarea}", 'Elige un proveedor de la lista.');

            return;
        }

        if (IaConfiguracion::where('tarea', $tarea)->where('proveedor', $proveedor)->exists()) {
            $this->addError("nuevo.{$tarea}", 'Ese proveedor ya está en esta tarea.');

            return;
        }

        IaConfiguracion::create([
            'tarea' => $tarea,
            'proveedor' => $proveedor,
            'modelo' => null,
            'orden' => (int) IaConfiguracion::where('tarea', $tarea)->max('orden') + 1,
            'timeout' => min(180, IaConfiguracion::timeoutMaximo($tarea)),
            'activo' => true,
        ]);

        unset($this->nuevo[$tarea]);
        $this->cargarFormulario();
    }

    /**
     * Sube (-1) o baja (+1) una fila dentro de su tarea. El primero activo es el principal.
     */
    public function mover(int $id, int $direccion): void
    {
        $fila = IaConfiguracion::findOrFail($id);
        $filas = $this->filasDe($fila->tarea);
        $i = array_search($fila->id, array_map(fn (IaConfiguracion $f) => $f->id, $filas), true);
        $j = $i + ($direccion < 0 ? -1 : 1);

        if (! isset($filas[$j])) {
            return;
        }

        [$filas[$i], $filas[$j]] = [$filas[$j], $filas[$i]];
        $this->renumerar($filas);
        $this->cargarFormulario();
    }

    public function alternar(int $id): void
    {
        $fila = IaConfiguracion::findOrFail($id);

        if ($fila->activo && $this->esElUltimoActivo($fila)) {
            $this->addError("tarea.{$fila->tarea}", 'Cada tarea necesita al menos un proveedor activo.');

            return;
        }

        $fila->update(['activo' => ! $fila->activo]);
        $this->cargarFormulario();
    }

    public function quitar(int $id): void
    {
        $fila = IaConfiguracion::findOrFail($id);

        if ($fila->activo && $this->esElUltimoActivo($fila)) {
            $this->addError("tarea.{$fila->tarea}", 'Cada tarea necesita al menos un proveedor activo.');

            return;
        }

        $fila->delete();
        $this->renumerar($this->filasDe($fila->tarea));
        $this->cargarFormulario();
    }

    /**
     * Llamada corta con el proveedor y el modelo de la fila, sin respaldo. Se registra como tarea `prueba`.
     */
    public function probar(int $id, ClienteIA $ia): void
    {
        $fila = IaConfiguracion::findOrFail($id);
        set_time_limit(IaConfiguracion::timeoutMaximo($fila->tarea) + 30);

        try {
            if ($fila->tarea === 'imagen') {
                $r = $ia->generarImagen('Un faro en la costa asturiana', 'landscape', auth()->user(), 'prueba', $fila);
                $texto = 'Imagen creada';
            } else {
                $r = $ia->generarEstructurado(
                    tarea: 'prueba',
                    instrucciones: 'Eres una herramienta para maestras de Primaria en Asturias. Responde en español de España.',
                    peticion: 'Propón un título corto para un proyecto de 3.º de Primaria sobre los ríos de Asturias.',
                    esquema: fn (JsonSchema $s) => ['titulo' => $s->string()->required()],
                    usuario: auth()->user(),
                    soloEsta: $fila,
                );
                $texto = '«'.($r['datos']['titulo'] ?? '—').'»';
            }

            $g = $r['generacion'];
            $this->prueba = ['id' => $id, 'ok' => true, 'texto' => "{$texto} · {$g->modelo} · {$g->duracion_ms} ms", 'url' => $r['url'] ?? null];
        } catch (Throwable $e) {
            $this->prueba = ['id' => $id, 'ok' => false, 'texto' => str($e->getMessage())->limit(300)->toString(), 'url' => null];
        }
    }

    /**
     * @return array<int, IaConfiguracion>
     */
    private function filasDe(string $tarea): array
    {
        return IaConfiguracion::where('tarea', $tarea)->orderBy('orden')->orderBy('id')->get()->all();
    }

    /**
     * @param  array<int, IaConfiguracion>  $filas
     */
    private function renumerar(array $filas): void
    {
        foreach (array_values($filas) as $n => $f) {
            $f->update(['orden' => $n + 1]);
        }
    }

    private function esElUltimoActivo(IaConfiguracion $fila): bool
    {
        return ! IaConfiguracion::where('tarea', $fila->tarea)->where('activo', true)->whereKeyNot($fila->id)->exists();
    }

    private function cargarFormulario(): void
    {
        foreach (IaConfiguracion::all() as $f) {
            $this->modelos[$f->id] = (string) $f->modelo;
            $this->timeouts[$f->id] = $f->timeout;
        }

        unset($this->configuraciones);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración de la IA</h2>
        <p class="text-sm text-gray-500 mt-1">El primer proveedor activo de cada tarea es el principal. Si falla o limita la tasa, responde el siguiente. Los cambios se aplican en la siguiente generación.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-action-message on="ia-guardada" class="text-sm text-green-700">Guardado.</x-action-message>

            @foreach (\App\Models\IaConfiguracion::TAREAS as $tarea => $nombreTarea)
                @php
                    $filas = $this->configuraciones[$tarea] ?? collect();
                    $activas = $filas->where('activo', true)->values();
                    $disponibles = collect(\App\Models\IaConfiguracion::proveedoresPara($tarea))->except($filas->pluck('proveedor')->all());
                @endphp

                <section wire:key="tarea-{{ $tarea }}" class="bg-white shadow-sm sm:rounded-lg p-5 space-y-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ $nombreTarea }}</h3>
                        <span class="text-xs text-gray-500">Tiempo máximo: hasta {{ \App\Models\IaConfiguracion::timeoutMaximo($tarea) }} s</span>
                    </div>

                    @if ($activas->isEmpty())
                        <p class="rounded-md bg-red-50 p-3 text-sm text-red-800">No hay ningún proveedor activo: esta tarea no funcionará.</p>
                    @endif
                    <x-input-error :messages="$errors->get('tarea.'.$tarea)" />

                    <ul class="divide-y divide-gray-100">
                        @foreach ($filas as $f)
                            @php($posicion = $activas->search(fn ($a) => $a->id === $f->id))
                            <li wire:key="fila-{{ $f->id }}" class="py-3 space-y-2">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    <div class="min-w-48">
                                        <span class="font-medium text-gray-900">{{ \App\Models\IaConfiguracion::proveedoresPara($tarea)[$f->proveedor] ?? $f->proveedor }}</span>
                                        <span class="ms-1 rounded-full px-2 py-0.5 text-xs {{ $posicion === 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ ! $f->activo ? 'Desactivado' : ($posicion === 0 ? 'Principal' : 'Respaldo '.$posicion) }}
                                        </span>
                                        @unless ($f->tieneClave())
                                            <span class="ms-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800" title="Falta la clave en .env">Sin clave</span>
                                        @endunless
                                    </div>

                                    <form wire:submit="guardar({{ $f->id }})" class="flex flex-wrap items-center gap-2">
                                        <x-text-input wire:model="modelos.{{ $f->id }}" type="text" class="w-56 text-sm" placeholder="Modelo por defecto" aria-label="Modelo" />
                                        <x-text-input wire:model="timeouts.{{ $f->id }}" type="number" min="10" :max="\App\Models\IaConfiguracion::timeoutMaximo($tarea)" class="w-24 text-sm" aria-label="Tiempo máximo en segundos" />
                                        <span class="text-xs text-gray-500">s</span>
                                        <x-secondary-button type="submit">Guardar</x-secondary-button>
                                    </form>

                                    <div class="flex flex-wrap items-center gap-3 text-sm">
                                        <button type="button" wire:click="mover({{ $f->id }}, -1)" @disabled($loop->first) class="text-gray-700 hover:underline disabled:opacity-40" aria-label="Subir">↑</button>
                                        <button type="button" wire:click="mover({{ $f->id }}, 1)" @disabled($loop->last) class="text-gray-700 hover:underline disabled:opacity-40" aria-label="Bajar">↓</button>
                                        <button type="button" wire:click="alternar({{ $f->id }})" class="text-indigo-700 hover:underline">{{ $f->activo ? 'Desactivar' : 'Activar' }}</button>
                                        <button type="button" wire:click="probar({{ $f->id }})" wire:loading.attr="disabled" wire:target="probar({{ $f->id }})" class="text-indigo-700 hover:underline">Probar</button>
                                        <button type="button" wire:click="quitar({{ $f->id }})" wire:confirm="¿Quitar este proveedor de la tarea?" class="text-red-700 hover:underline">Quitar</button>
                                        <span wire:loading wire:target="probar({{ $f->id }})" class="text-gray-500">Probando…</span>
                                    </div>
                                </div>
                                <x-input-error :messages="array_merge($errors->get('modelos.'.$f->id), $errors->get('timeouts.'.$f->id))" />

                                @if ($prueba && $prueba['id'] === $f->id)
                                    <div class="rounded-md p-3 text-sm {{ $prueba['ok'] ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                                        {{ $prueba['ok'] ? 'Funciona: ' : 'Ha fallado: ' }}{{ $prueba['texto'] }}
                                        @if ($prueba['url'])
                                            <img src="{{ $prueba['url'] }}" alt="Imagen de prueba" class="mt-2 max-h-40 rounded">
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($disponibles->isNotEmpty())
                        <form wire:submit="anadir('{{ $tarea }}')" class="flex flex-wrap items-center gap-2 pt-1">
                            <select wire:model="nuevo.{{ $tarea }}" class="rounded-md border-gray-300 text-sm" aria-label="Proveedor que añadir">
                                <option value="">Añadir proveedor…</option>
                                @foreach ($disponibles as $clave => $nombre)
                                    <option value="{{ $clave }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                            <x-secondary-button type="submit">Añadir</x-secondary-button>
                            <x-input-error :messages="$errors->get('nuevo.'.$tarea)" />
                        </form>
                    @endif
                </section>
            @endforeach

            <p class="text-xs text-gray-500">Cada intento usa el tiempo máximo más alto de la tarea. Si el principal lo agota, puede que el respaldo no llegue a responder antes de que la cola corte el trabajo: el respaldo sirve sobre todo para errores rápidos (caídas o límite de tasa).</p>
        </div>
    </div>
</div>

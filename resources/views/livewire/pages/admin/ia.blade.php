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
new #[Layout('layouts.admin')] class extends Component
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
        <x-cabecera antetitulo="Administración" titulo="Configuración de la IA">
            El primer proveedor activo de cada tarea es el principal. Si falla o limita la tasa, responde el siguiente. Los cambios se aplican en la siguiente generación.
        </x-cabecera>
    </x-slot>

    <div class="contenedor space-y-5">
        <x-action-message on="ia-guardada" class="aviso-pino">Guardado.</x-action-message>

        @foreach (\App\Models\IaConfiguracion::TAREAS as $tarea => $nombreTarea)
            @php
                $filas = $this->configuraciones[$tarea] ?? collect();
                $activas = $filas->where('activo', true)->values();
                $disponibles = collect(\App\Models\IaConfiguracion::proveedoresPara($tarea))->except($filas->pluck('proveedor')->all());
            @endphp

            <section wire:key="tarea-{{ $tarea }}" class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-9 items-center justify-center rounded-xl bg-stone-50 text-stone-600 ring-1 ring-inset ring-stone-200">
                            <x-icono :nombre="['proyecto' => 'tablero', 'ajuste' => 'chispa', 'ficha' => 'ficha', 'imagen' => 'imagen', 'prueba' => 'matraz'][$tarea] ?? 'ajustes'" class="size-[18px]" />
                        </span>
                        <h3 class="tarjeta-titulo">{{ $nombreTarea }}</h3>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-stone-500"><x-icono nombre="reloj" class="size-3.5" />Tiempo máximo: hasta {{ \App\Models\IaConfiguracion::timeoutMaximo($tarea) }} s</span>
                </div>

                @if ($activas->isEmpty())
                    <div class="px-5 pt-4 sm:px-6">
                        <p class="aviso-rojo"><x-icono nombre="aviso" class="size-5 text-red-700" />No hay ningún proveedor activo: esta tarea no funcionará.</p>
                    </div>
                @endif
                <x-input-error class="px-5 pt-3 sm:px-6" :messages="$errors->get('tarea.'.$tarea)" />

                <ul class="divide-y divide-stone-100">
                    @foreach ($filas as $f)
                        @php $posicion = $activas->search(fn ($a) => $a->id === $f->id); @endphp
                        <li wire:key="fila-{{ $f->id }}" @class(['space-y-3 px-5 py-4 sm:px-6', 'bg-stone-50/60' => ! $f->activo])>
                            <div class="grid items-center gap-x-6 gap-y-3 xl:grid-cols-[minmax(0,15rem)_minmax(0,1fr)_auto]">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="cifra inline-flex size-6 items-center justify-center rounded-md bg-stone-100 text-[11px] font-medium text-stone-500">{{ $loop->iteration }}</span>
                                    <span @class(['font-medium', 'text-stone-950' => $f->activo, 'text-stone-400 line-through decoration-stone-300' => ! $f->activo])>{{ \App\Models\IaConfiguracion::proveedoresPara($tarea)[$f->proveedor] ?? $f->proveedor }}</span>
                                    <span @class([
                                        'insignia-pino' => $f->activo && $posicion === 0,
                                        'insignia-neutra' => ! ($f->activo && $posicion === 0),
                                    ])>
                                        @if ($f->activo && $posicion === 0)<span class="punto-vivo size-1.5"></span>@endif
                                        {{ ! $f->activo ? 'Desactivado' : ($posicion === 0 ? 'Principal' : 'Respaldo '.$posicion) }}
                                    </span>
                                    @unless ($f->tieneClave())
                                        <span class="insignia-ambar" title="Falta la clave en .env"><x-icono nombre="llave" class="size-3" />Sin clave</span>
                                    @endunless
                                </div>

                                @php
                                    $sugeridos = \App\Models\IaConfiguracion::modelosPara($tarea, $f->proveedor);
                                    $porDefecto = \App\Models\IaConfiguracion::modeloPorDefecto($tarea, $f->proveedor);
                                    $modelo = $modelos[$f->id] ?? '';
                                @endphp
                                <form wire:submit="guardar({{ $f->id }})" x-data="{ otro: @js(filled($modelo) && ! array_key_exists($modelo, $sugeridos)) }" class="flex flex-wrap items-center gap-2">
                                    <select x-show="! otro" wire:model="modelos.{{ $f->id }}" class="campo w-full py-2 font-mono text-[13px] sm:w-72" aria-label="Modelo">
                                        <option value="">Por defecto{{ $porDefecto ? ' ('.$porDefecto.')' : '' }}</option>
                                        @foreach ($sugeridos as $id => $etiqueta)
                                            <option value="{{ $id }}">{{ $id }} · {{ $etiqueta }}</option>
                                        @endforeach
                                        @if (filled($modelo) && ! array_key_exists($modelo, $sugeridos))
                                            <option value="{{ $modelo }}">{{ $modelo }}</option>
                                        @endif
                                    </select>
                                    <x-text-input x-show="otro" x-cloak wire:model="modelos.{{ $f->id }}" type="text" class="w-full py-2 font-mono text-[13px] sm:w-72" placeholder="Nombre exacto del modelo" aria-label="Otro modelo" />
                                    <button type="button" x-on:click="otro = ! otro" class="boton-fantasma boton-sm" x-text="otro ? 'Lista' : 'Otro'">Otro</button>
                                    <div class="relative">
                                        <x-text-input wire:model="timeouts.{{ $f->id }}" type="number" min="10" :max="\App\Models\IaConfiguracion::timeoutMaximo($tarea)" class="cifra w-24 py-2 pe-7 text-[13px]" aria-label="Tiempo máximo en segundos" />
                                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-stone-400">s</span>
                                    </div>
                                    <x-secondary-button type="submit" class="boton-sm py-2">Guardar</x-secondary-button>
                                </form>

                                <div class="flex flex-wrap items-center gap-1">
                                    <button type="button" wire:click="mover({{ $f->id }}, -1)" @disabled($loop->first) class="boton-icono" aria-label="Subir"><x-icono nombre="flecha-arriba" class="size-4" /></button>
                                    <button type="button" wire:click="mover({{ $f->id }}, 1)" @disabled($loop->last) class="boton-icono" aria-label="Bajar"><x-icono nombre="flecha-abajo" class="size-4" /></button>
                                    <span class="mx-1 h-5 w-px bg-stone-200"></span>
                                    <button type="button" wire:click="alternar({{ $f->id }})" class="boton-fantasma boton-sm">{{ $f->activo ? 'Desactivar' : 'Activar' }}</button>
                                    <button type="button" wire:click="probar({{ $f->id }})" wire:loading.attr="disabled" wire:target="probar({{ $f->id }})" class="boton-fantasma boton-sm text-pino-700 hover:text-pino-900">
                                        <x-icono nombre="matraz" class="size-4" wire:loading.remove wire:target="probar({{ $f->id }})" />
                                        <span class="punto-vivo" wire:loading wire:target="probar({{ $f->id }})"></span>
                                        Probar
                                    </button>
                                    <button type="button" wire:click="quitar({{ $f->id }})" wire:confirm="¿Quitar este proveedor de la tarea?" class="boton-icono hover:bg-red-50 hover:text-red-700" aria-label="Quitar" title="Quitar"><x-icono nombre="papelera" class="size-4" /></button>
                                    <span wire:loading wire:target="probar({{ $f->id }})" class="ms-1 text-xs text-stone-500">Probando…</span>
                                </div>
                            </div>
                            <x-input-error :messages="array_merge($errors->get('modelos.'.$f->id), $errors->get('timeouts.'.$f->id))" />

                            @if ($prueba && $prueba['id'] === $f->id)
                                <div @class(['animate-aparecer', 'aviso-pino' => $prueba['ok'], 'aviso-rojo' => ! $prueba['ok']])>
                                    <x-icono :nombre="$prueba['ok'] ? 'check-circulo' : 'aviso'" @class(['size-5', 'text-pino-700' => $prueba['ok'], 'text-red-700' => ! $prueba['ok']]) />
                                    <div class="min-w-0 break-words">
                                        {{ $prueba['ok'] ? 'Funciona: ' : 'Ha fallado: ' }}{{ $prueba['texto'] }}
                                        @if ($prueba['url'])
                                            <img src="{{ $prueba['url'] }}" alt="Imagen de prueba" class="mt-3 max-h-40 rounded-lg ring-1 ring-black/5">
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($disponibles->isNotEmpty())
                    <form wire:submit="anadir('{{ $tarea }}')" class="flex flex-wrap items-center gap-2 border-t border-stone-100 bg-stone-50/60 px-5 py-3.5 sm:px-6">
                        <select wire:model="nuevo.{{ $tarea }}" class="campo w-auto py-2 text-[13px]" aria-label="Proveedor que añadir">
                            <option value="">Añadir proveedor…</option>
                            @foreach ($disponibles as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit" class="boton-sm py-2"><x-icono nombre="mas" class="size-4" />Añadir</x-secondary-button>
                        <x-input-error :messages="$errors->get('nuevo.'.$tarea)" />
                    </form>
                @endif
            </section>
        @endforeach

        <p class="flex items-start gap-2 text-[13px] leading-relaxed text-stone-500"><x-icono nombre="info" class="mt-0.5 size-4 text-stone-400" />Cada intento usa el tiempo máximo más alto de la tarea. Si el principal lo agota, puede que el respaldo no llegue a responder antes de que la cola corte el trabajo: el respaldo sirve sobre todo para errores rápidos (caídas o límite de tasa).</p>
    </div>
</div>

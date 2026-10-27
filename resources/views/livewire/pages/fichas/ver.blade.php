<?php

use App\Jobs\GenerarFicha;
use App\Models\Ficha;
use App\View\EstilosDocumento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    /** Imagen hecha con ChatGPT o Gemini que sube la docente. */
    public $nuevaImagen = null;
    public Ficha $ficha;

    public string $nivel = 'estandar';

    public bool $confirmarBorrado = false;

    /**
     * Textos de la ficha que la docente puede editar a mano (rutas dentro de `contenido`).
     */
    private const RUTAS_EDITABLES = '/^(refuerzo|estandar|ampliacion)\.(consigna|bloques\.\d+\.(titulo|enunciado|texto|preguntas\.\d+|items\.\d+\.pregunta|items\.\d+\.opciones\.\d+|frases\.\d+|banco\.\d+|columnas\.\d+|filas\.\d+\.\d+|izquierda\.\d+|derecha\.\d+))$/';

    public function mount(Ficha $ficha): void
    {
        $this->authorize('view', $ficha);
        $this->ficha = $ficha->load('criterios');
    }

    public function refrescar(): void
    {
        $this->ficha->refresh()->load('criterios');
    }

    public function verNivel(string $nivel): void
    {
        if (array_key_exists($nivel, Ficha::NIVELES)) {
            $this->nivel = $nivel;
        }
    }

    /**
     * '' vuelve al estilo del perfil de la docente.
     */
    public function cambiarEstilo(string $estilo): void
    {
        $this->authorize('update', $this->ficha);

        if ($estilo === '' || EstilosDocumento::existe($estilo)) {
            $this->ficha->forceFill(['estilo' => $estilo ?: null])->save();
        }
    }

    public function actualizar(string $ruta, string $valor): void
    {
        $this->authorize('update', $this->ficha);
        $this->skipRender();

        if ($this->ficha->estaGenerando()) {
            return;
        }

        $valor = str(trim($valor))->limit($ruta === 'titulo' ? 255 : 2000, '')->toString();

        if ($ruta === 'titulo') {
            $this->ficha->forceFill(['titulo' => $valor])->save();

            return;
        }

        $contenido = $this->ficha->contenido ?? [];
        if (! preg_match(self::RUTAS_EDITABLES, $ruta) || ! is_string(data_get($contenido, $ruta))) {
            return;
        }

        data_set($contenido, $ruta, $valor);
        $this->ficha->forceFill(['contenido' => $contenido])->save();
    }

    public function regenerar(): void
    {
        $this->authorize('update', $this->ficha);

        if ($this->ficha->estaGenerando()) {
            return;
        }

        $this->ficha->forceFill(['estado' => 'generando', 'error' => null])->save();
        GenerarFicha::dispatch($this->ficha);
        $this->ficha->refresh();
    }

    public function updatedNuevaImagen(): void
    {
        $this->authorize('update', $this->ficha);

        $this->validate(
            ['nuevaImagen' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240']],
            ['nuevaImagen.image' => 'Sube una imagen (PNG, JPG o WebP).', 'nuevaImagen.mimes' => 'Sube una imagen PNG, JPG o WebP.', 'nuevaImagen.max' => 'La imagen no puede pasar de 10 MB.'],
        );

        $ruta = $this->nuevaImagen->storePubliclyAs(
            'imagenes/'.$this->ficha->user_id,
            Str::ulid()->toBase32().'.'.$this->nuevaImagen->extension(),
            'public',
        );

        $anterior = $this->ficha->imagen;
        $this->ficha->forceFill(['imagen' => $ruta, 'error' => null])->save();
        $this->reset('nuevaImagen');

        if ($anterior && $anterior !== $ruta) {
            Storage::disk('public')->delete($anterior);
        }
    }

    public function quitarImagen(): void
    {
        $this->authorize('update', $this->ficha);

        if ($this->ficha->imagen) {
            Storage::disk('public')->delete($this->ficha->imagen);
            $this->ficha->forceFill(['imagen' => null])->save();
        }
    }

    public function borrar(): void
    {
        $this->authorize('delete', $this->ficha);
        $this->ficha->delete();

        $this->redirectRoute('fichas', navigate: true);
    }
}; ?>

<div @if ($ficha->estaGenerando()) wire:poll.3s="refrescar" @endif>
    {{-- Cabecera dentro del componente: el título llega con el polling al terminar de generar. --}}
    <div class="contenedor pb-8 pt-10 sm:pt-12">
        <x-cabecera antetitulo="Ficha en 3 niveles" :titulo="$ficha->titulo ?: 'Nueva ficha'" :volver="route('fichas')" volver-texto="Todas las fichas">
            {{ \App\Models\Area::where('codigo', $ficha->area)->value('nombre_corto') ?? $ficha->area }} · {{ $ficha->curso }}.º de Primaria
            @if ($ficha->proyecto)
                <x-slot:acciones>
                    <a href="{{ route('proyectos.ver', $ficha->proyecto) }}" wire:navigate class="boton-secundario"><x-icono nombre="tablero" class="size-4" />Ir al proyecto</a>
                </x-slot:acciones>
            @endif
        </x-cabecera>
    </div>

    <div class="contenedor space-y-5">
        @if ($ficha->estaGenerando())
            <x-generando forma="ficha">
                Preparando la ficha en tres niveles{{ $ficha->con_imagen && ! $ficha->ilustracionPorPrompt() ? ' y su ilustración' : '' }}…
            </x-generando>
        @endif

        @if ($ficha->error)
            <div class="aviso-ambar flex-wrap items-center">
                <x-icono nombre="aviso" class="size-5 text-amber-700" />
                <span class="flex-1">{{ $ficha->error }}</span>
                @if ($ficha->estado === 'error')
                    <x-secondary-button wire:click="regenerar" class="boton-sm"><x-icono nombre="refrescar" class="size-4" />Volver a intentarlo</x-secondary-button>
                @endif
            </div>
        @endif

        @if ($ficha->contenido)
            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="segmentado" role="group" aria-label="Nivel">
                            @foreach (\App\Models\Ficha::NIVELES as $clave => $nombre)
                                @php $puntos = ['refuerzo' => 1, 'estandar' => 2, 'ampliacion' => 3][$clave]; @endphp
                                <button type="button" wire:click="verNivel('{{ $clave }}')" aria-pressed="{{ $nivel === $clave ? 'true' : 'false' }}"
                                    @class(['segmento', 'segmento-activo' => $nivel === $clave])>
                                    <span class="flex gap-0.5" aria-hidden="true">
                                        @foreach ([1, 2, 3] as $k)
                                            <i @class(['block size-1.5 rounded-full ring-1', 'bg-pino-600 ring-pino-600' => $k <= $puntos, 'ring-stone-400' => $k > $puntos])></i>
                                        @endforeach
                                    </span>
                                    {{ $nombre }}
                                </button>
                            @endforeach
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-selector-estilo :actual="$ficha->estilo" />
                            <a href="{{ route('fichas.imprimir', [$ficha, 'nivel' => $nivel]) }}" target="_blank" class="boton-secundario"><x-icono nombre="imprimir" class="size-4" />Imprimir</a>
                            <a href="{{ route('fichas.imprimir', [$ficha, 'nivel' => 'todos']) }}" target="_blank" class="boton-secundario">Los 3 niveles</a>
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] bg-stone-200/50 p-3 ring-1 ring-inset ring-stone-200 sm:p-8">
                        <div class="overflow-hidden rounded-sm shadow-papel" wire:loading.class="opacity-60" wire:target="verNivel">
                            <x-ficha.hoja :ficha="$ficha" :nivel="$nivel" :editable="! $ficha->estaGenerando()" wire:key="hoja-{{ $nivel }}-{{ $ficha->updated_at?->timestamp }}" />
                        </div>
                    </div>
                    <p class="flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-stone-500">
                        <span class="inline-flex items-center gap-1.5"><x-icono nombre="lapiz" class="size-4 text-stone-400" />Haz clic en cualquier texto para editarlo; se guarda al salir del campo.</span>
                        <span class="inline-flex items-center gap-1.5"><x-icono nombre="ojo-tachado" class="size-4 text-stone-400" />En la hoja impresa el nivel solo se ve como puntos (● ●● ●●●).</span>
                    </p>
                </div>

                <aside class="space-y-4 xl:sticky xl:top-24">
                    @if ($ficha->prompt_imagen && ! $ficha->estaGenerando() && ($ficha->ilustracionPorPrompt() || ! $ficha->imagen || $ficha->error))
                        <section class="tarjeta p-5 text-sm" x-data="{ copiado: false }">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="imagen" class="size-4" /></span>
                                <h3 class="font-semibold text-stone-950">Ilustración con ChatGPT o Gemini</h3>
                            </div>
                            <p class="mt-3 leading-relaxed text-stone-600">Copia este texto, pégalo en
                                <a href="https://chatgpt.com/" target="_blank" rel="noopener" class="enlace">ChatGPT</a> o
                                <a href="https://gemini.google.com/app" target="_blank" rel="noopener" class="enlace">Gemini</a>,
                                descarga la imagen y súbela aquí. Puedes cambiar el texto antes de copiarlo.</p>
                            <textarea x-ref="prompt" rows="5" class="campo mt-3 text-[13px] leading-relaxed" aria-label="Prompt para la ilustración">{{ $ficha->prompt_imagen }}</textarea>
                            <div class="mt-3 grid gap-2">
                                <x-secondary-button type="button" class="w-full"
                                    x-on:click="navigator.clipboard.writeText($refs.prompt.value).then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })">
                                    <span x-show="! copiado" class="inline-flex items-center gap-2"><x-icono nombre="copiar" class="size-4" />Copiar el prompt</span><span x-show="copiado" style="display: none" class="inline-flex items-center gap-2 text-pino-700"><x-icono nombre="check" class="size-4" />Copiado</span>
                                </x-secondary-button>
                                <label class="boton-primario w-full cursor-pointer has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-pino-600 has-[:focus-visible]:ring-offset-2">
                                    <input type="file" wire:model="nuevaImagen" accept="image/png,image/jpeg,image/webp" class="sr-only">
                                    <x-icono nombre="subir" class="size-4" wire:loading.remove wire:target="nuevaImagen" />
                                    <span class="punto-vivo" wire:loading wire:target="nuevaImagen"></span>
                                    {{ $ficha->imagen ? 'Cambiar la imagen' : 'Subir la imagen' }}
                                </label>
                                <span wire:loading wire:target="nuevaImagen" class="text-center text-stone-500">Subiendo…</span>
                                @if ($ficha->imagen)
                                    <button type="button" wire:click="quitarImagen" class="enlace-peligro justify-center py-1 text-sm">Quitar la imagen</button>
                                @endif
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('nuevaImagen')" />
                        </section>
                    @endif

                    <section class="tarjeta p-5 text-sm">
                        <h3 class="text-xs font-medium text-stone-500">Lo que pediste:</h3>
                        <p class="mt-1 leading-relaxed text-stone-800">{{ $ficha->tema }}</p>

                        @if ($ficha->criterios->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-1">
                                @foreach ($ficha->criterios as $c)
                                    <span class="codigo" title="{{ $c->texto }}">{{ $ficha->area }} {{ $c->codigo }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 flex items-center gap-1.5 text-[13px] text-pino-700"><x-icono nombre="escudo" class="size-4" />Criterios comprobados con el decreto</p>
                        @endif
                        @if ($ficha->ids_descartados)
                            <p class="mt-2 text-[13px] text-stone-500">{{ $ficha->ids_descartados }} criterios inventados por la IA se han descartado.</p>
                        @endif

                        <div class="mt-5 space-y-3 border-t border-stone-100 pt-4">
                            <button type="button" wire:click="regenerar" @disabled($ficha->estaGenerando()) class="enlace text-left"><x-icono nombre="refrescar" class="size-4" />Generar de nuevo (sustituye la actual)</button>
                            @if ($confirmarBorrado)
                                <div class="aviso-rojo flex-col animate-aparecer">
                                    <span class="text-red-800">¿Borrar esta ficha?</span>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="$set('confirmarBorrado', false)" class="boton-secundario boton-sm">Cancelar</button>
                                        <button type="button" wire:click="borrar" class="boton-peligro boton-sm">Sí, borrar</button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="$set('confirmarBorrado', true)" class="enlace-peligro"><x-icono nombre="papelera" class="size-4" />Borrar ficha</button>
                            @endif
                        </div>
                    </section>
                </aside>
            </div>
        @endif
    </div>
</div>

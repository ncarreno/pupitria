<?php

use App\Jobs\GenerarFicha;
use App\Models\Ficha;
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
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $ficha->titulo ?: 'Nueva ficha' }}</h2>
            <div class="flex gap-4 text-sm">
                @if ($ficha->proyecto)
                    <a href="{{ route('proyectos.ver', $ficha->proyecto) }}" wire:navigate class="text-indigo-700 hover:underline">Ir al proyecto</a>
                @endif
                <a href="{{ route('fichas') }}" wire:navigate class="text-indigo-700 hover:underline">Todas las fichas</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($ficha->estaGenerando())
                <div class="bg-indigo-50 text-indigo-900 sm:rounded-lg p-4 text-sm flex items-center gap-3">
                    <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"/></svg>
                    Preparando la ficha en tres niveles{{ $ficha->con_imagen && ! $ficha->ilustracionPorPrompt() ? ' y su ilustración' : '' }}… Suele tardar entre 30 y 90 segundos.
                </div>
            @endif

            @if ($ficha->error)
                <div class="bg-amber-50 text-amber-900 sm:rounded-lg p-4 text-sm flex flex-wrap items-center gap-3">
                    <span>{{ $ficha->error }}</span>
                    @if ($ficha->estado === 'error')
                        <x-secondary-button wire:click="regenerar">Volver a intentarlo</x-secondary-button>
                    @endif
                </div>
            @endif

            @if ($ficha->contenido)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex rounded-md border border-gray-300 bg-white overflow-hidden" role="group" aria-label="Nivel">
                        @foreach (\App\Models\Ficha::NIVELES as $clave => $nombre)
                            <button type="button" wire:click="verNivel('{{ $clave }}')" aria-pressed="{{ $nivel === $clave ? 'true' : 'false' }}"
                                class="px-4 py-2 text-sm {{ $nivel === $clave ? 'bg-gray-800 text-white font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">{{ $nombre }}</button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">En la hoja impresa el nivel solo se ve como puntos (● ●● ●●●).</span>
                        <x-secondary-button type="button" onclick="window.print()">Imprimir</x-secondary-button>
                    </div>
                </div>

                @if ($ficha->prompt_imagen && ! $ficha->estaGenerando() && ($ficha->ilustracionPorPrompt() || ! $ficha->imagen || $ficha->error))
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm space-y-3" x-data="{ copiado: false }">
                        <div>
                            <h3 class="font-medium text-gray-900">Ilustración con ChatGPT o Gemini</h3>
                            <p class="text-gray-600">Copia este texto, pégalo en
                                <a href="https://chatgpt.com/" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">ChatGPT</a> o
                                <a href="https://gemini.google.com/app" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">Gemini</a>,
                                descarga la imagen y súbela aquí. Puedes cambiar el texto antes de copiarlo.</p>
                        </div>
                        <textarea x-ref="prompt" rows="4" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" aria-label="Prompt para la ilustración">{{ $ficha->prompt_imagen }}</textarea>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-secondary-button type="button"
                                x-on:click="navigator.clipboard.writeText($refs.prompt.value).then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })">
                                <span x-show="! copiado">Copiar el prompt</span><span x-show="copiado" style="display: none">Copiado</span>
                            </x-secondary-button>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-indigo-700 hover:underline">
                                <input type="file" wire:model="nuevaImagen" accept="image/png,image/jpeg,image/webp" class="sr-only">
                                {{ $ficha->imagen ? 'Cambiar la imagen' : 'Subir la imagen' }}
                            </label>
                            @if ($ficha->imagen)
                                <button type="button" wire:click="quitarImagen" class="text-red-700 hover:underline">Quitar la imagen</button>
                            @endif
                            <span wire:loading wire:target="nuevaImagen" class="text-gray-500">Subiendo…</span>
                        </div>
                        <x-input-error :messages="$errors->get('nuevaImagen')" />
                    </div>
                @endif

                <div class="shadow-sm sm:rounded-lg overflow-hidden">
                    <x-ficha.hoja :ficha="$ficha" :nivel="$nivel" :editable="! $ficha->estaGenerando()" wire:key="hoja-{{ $nivel }}-{{ $ficha->updated_at?->timestamp }}" />
                </div>
                <p class="text-xs text-gray-500">Haz clic en cualquier texto para editarlo; se guarda al salir del campo.</p>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm space-y-2">
                    <p class="text-gray-700"><span class="font-medium">Lo que pediste:</span> {{ $ficha->tema }}</p>
                    @if ($ficha->ids_descartados)
                        <p class="text-gray-500">{{ $ficha->ids_descartados }} criterios inventados por la IA se han descartado.</p>
                    @endif
                    <div class="flex flex-wrap gap-4">
                        <button type="button" wire:click="regenerar" @disabled($ficha->estaGenerando()) class="text-indigo-700 hover:underline">Generar de nuevo (sustituye la actual)</button>
                        @if ($confirmarBorrado)
                            <span class="text-red-800">¿Borrar esta ficha?</span>
                            <button type="button" wire:click="borrar" class="text-red-700 font-semibold hover:underline">Sí, borrar</button>
                            <button type="button" wire:click="$set('confirmarBorrado', false)" class="text-gray-700 hover:underline">Cancelar</button>
                        @else
                            <button type="button" wire:click="$set('confirmarBorrado', true)" class="text-red-700 hover:underline">Borrar ficha</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

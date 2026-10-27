{{-- Duplicar un proyecto o una ficha en otra clase del mismo curso. La página tiene `$duplicando`, `$claseDestino`, `abrirDuplicar()` y `duplicar()`. --}}
@props(['abierto', 'clases', 'curso', 'que'])

@if ($abierto)
    <form wire:submit="duplicar" class="grid gap-3 rounded-xl bg-stone-50 p-3 ring-1 ring-inset ring-stone-200 animate-aparecer">
        <label class="grid gap-1.5">
            <span class="text-[13px] font-medium text-stone-700">Duplicar en la clase</span>
            <select wire:model="claseDestino" class="campo text-sm">
                @foreach ($clases as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->curso_escolar }})</option>
                @endforeach
            </select>
            <span class="text-xs text-stone-500">Solo tus clases de {{ $curso }}.º: el contenido y los criterios son de ese curso.</span>
        </label>
        <x-input-error :messages="$errors->get('claseDestino')" />
        <div class="flex gap-2">
            <x-secondary-button wire:click="$set('duplicando', false)" class="boton-sm">Cancelar</x-secondary-button>
            <x-primary-button class="boton-sm">Duplicar</x-primary-button>
        </div>
    </form>
@else
    <button type="button" wire:click="abrirDuplicar" class="enlace text-left text-sm"><x-icono nombre="copiar" class="size-4" />Duplicar {{ $que }}</button>
@endif

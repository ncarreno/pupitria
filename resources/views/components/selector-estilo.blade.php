{{-- Elige el estilo de un documento. La página que lo usa tiene el método `cambiarEstilo(string)`; '' = el del perfil. --}}
@props(['actual' => null])
@php $delPerfil = \App\View\EstilosDocumento::resolver(null, auth()->user()); @endphp

<label {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-stone-600']) }}>
    <x-icono nombre="paleta" class="size-4 text-stone-400" />
    <span class="sr-only sm:not-sr-only">Estilo</span>
    <select wire:change="cambiarEstilo($event.target.value)" class="campo w-auto py-1.5 text-sm" aria-label="Estilo del documento">
        <option value="" @selected($actual === null)>El de tu perfil ({{ \App\View\EstilosDocumento::nombre($delPerfil) }})</option>
        @foreach (\App\View\EstilosDocumento::ESTILOS as $clave => $estilo)
            <option value="{{ $clave }}" @selected($actual === $clave)>{{ $estilo['nombre'] }}</option>
        @endforeach
    </select>
</label>

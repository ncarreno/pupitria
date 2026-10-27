@props(['active' => false, 'icono' => null])

@php
$classes = $active
            ? 'flex w-full items-center gap-3 rounded-xl bg-white px-3 py-2.5 text-start text-base font-medium text-stone-950 shadow-sm ring-1 ring-stone-200 transition'
            : 'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-base font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-950';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icono)
        <x-icono :nombre="$icono" @class(['size-5', 'text-pino-700' => $active, 'text-stone-400' => ! $active]) />
    @endif
    {{ $slot }}
</a>

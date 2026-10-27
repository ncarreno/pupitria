@props(['active' => false, 'icono' => null])

@php
$classes = $active
            ? 'group inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-stone-950 shadow-[0_1px_2px_rgba(41,37,36,0.08)] ring-1 ring-stone-200 transition duration-200 ease-suave focus:outline-none focus-visible:ring-2 focus-visible:ring-pino-600'
            : 'group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition duration-200 ease-suave hover:bg-stone-200/50 hover:text-stone-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-pino-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    @if ($icono)
        <x-icono :nombre="$icono" @class(['size-[18px] transition', 'text-pino-700' => $active, 'text-stone-400 group-hover:text-stone-600' => ! $active]) />
    @endif
    {{ $slot }}
</a>

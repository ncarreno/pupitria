@props(['href' => null, 'oscura' => false, 'subtitulo' => 'Primaria · Asturias'])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-pino-600']) }}>
    <x-application-logo @class(['size-8 transition duration-300 ease-suave group-hover:-rotate-6', 'text-pino-500' => $oscura, 'text-pino-700' => ! $oscura]) />
    <span class="leading-none">
        <span @class(['block whitespace-nowrap text-[15px] font-semibold tracking-tight', 'text-white' => $oscura, 'text-stone-950' => ! $oscura])>{{ config('app.name') }}</span>
        @if ($subtitulo)
            <span @class(['mt-1 block text-[11px] font-medium', 'text-stone-400' => $oscura, 'text-stone-500' => ! $oscura])>{{ $subtitulo }}</span>
        @endif
    </span>
</{{ $tag }}>

@props(['titulo', 'antetitulo' => null, 'volver' => null, 'volverTexto' => null])
<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0 max-w-3xl animate-aparecer">
        @if ($volver)
            <a href="{{ $volver }}" wire:navigate class="group mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-stone-900">
                <x-icono nombre="flecha-izquierda" class="size-4 transition duration-300 ease-suave group-hover:-translate-x-0.5" />
                {{ $volverTexto }}
            </a>
        @endif
        @if ($antetitulo)
            <p class="antetitulo">{{ $antetitulo }}</p>
        @endif
        <h1 class="mt-2 text-balance text-3xl font-semibold leading-[1.1] tracking-tight text-stone-950 sm:text-4xl">{{ $titulo }}</h1>
        @if ($slot->isNotEmpty())
            <div class="mt-3 max-w-[65ch] text-[15px] leading-relaxed text-stone-600">{{ $slot }}</div>
        @endif
    </div>
    @isset($acciones)
        <div class="flex shrink-0 flex-wrap items-center gap-2 animate-aparecer [animation-delay:80ms]">{{ $acciones }}</div>
    @endisset
</div>

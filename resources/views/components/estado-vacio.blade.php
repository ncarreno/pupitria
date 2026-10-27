@props(['icono' => 'ficha', 'titulo'])
<div {{ $attributes->merge(['class' => 'tarjeta relative overflow-hidden px-6 py-14 sm:px-10']) }}>
    <div aria-hidden="true" class="cuadricula-clara pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top_left,black,transparent_65%)]"></div>
    <div class="relative max-w-lg">
        <div class="relative mb-6 inline-flex">
            <span class="absolute -right-3 -top-2 size-12 rotate-6 rounded-2xl bg-pino-100/80"></span>
            <span class="relative inline-flex size-12 items-center justify-center rounded-2xl bg-white text-pino-700 shadow-suave ring-1 ring-stone-200">
                <x-icono :nombre="$icono" class="size-6" />
            </span>
        </div>
        <h2 class="text-xl font-semibold tracking-tight text-stone-950">{{ $titulo }}</h2>
        <div class="mt-2 text-[15px] leading-relaxed text-stone-600">{{ $slot }}</div>
        @isset($acciones)
            <div class="mt-6 flex flex-wrap gap-2">{{ $acciones }}</div>
        @endisset
    </div>
</div>

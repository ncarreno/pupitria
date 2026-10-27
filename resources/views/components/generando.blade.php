@props(['forma' => 'documento', 'esqueleto' => true])
{{-- Estado de carga mientras la cola genera: aviso con el mensaje y un esqueleto con la forma del resultado. --}}
<div {{ $attributes->merge(['class' => 'space-y-4']) }} role="status" aria-live="polite">
    <div class="tarjeta relative overflow-hidden p-5 sm:p-6">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-px overflow-hidden bg-pino-100">
            <div class="h-full w-1/3 animate-brillo bg-gradient-to-r from-transparent via-pino-500 to-transparent"></div>
        </div>
        <div class="flex items-start gap-4">
            <span class="relative inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200">
                <x-icono nombre="chispa" class="size-5 animate-flotar" />
            </span>
            <div class="min-w-0 space-y-1">
                <p class="flex items-center gap-2 font-medium text-stone-950">
                    <span class="punto-vivo text-pino-600"></span>
                    {{ $slot }}
                </p>
                <p class="text-sm text-stone-500">Suele tardar entre 30 y 90 segundos. Esta página se actualiza sola: puedes dejarla abierta o volver luego.</p>
            </div>
        </div>
    </div>

    @if ($esqueleto)
    <div aria-hidden="true" @class(['tarjeta p-6 sm:p-8', 'mx-auto max-w-[820px]' => $forma === 'ficha'])>
        <div class="esqueleto h-9 w-2/3 rounded-lg"></div>
        <div class="esqueleto mt-3 h-4 w-1/3"></div>
        @if ($forma === 'ficha')
            <div class="mt-8 space-y-6">
                @foreach ([['w-1/2', 3], ['w-2/5', 4], ['w-1/3', 2]] as [$ancho, $lineas])
                    <div class="rounded-xl border border-stone-200 p-5">
                        <div class="esqueleto h-5 {{ $ancho }}"></div>
                        <div class="mt-4 space-y-2.5">
                            @for ($l = 0; $l < $lineas; $l++)
                                <div class="esqueleto h-3 {{ $l === $lineas - 1 ? 'w-3/5' : 'w-full' }}"></div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-8 grid gap-4 md:grid-cols-12">
                @foreach (['md:col-span-4', 'md:col-span-5', 'md:col-span-3', 'md:col-span-4', 'md:col-span-8'] as $col)
                    <div class="{{ $col }} rounded-xl border border-stone-200 p-5">
                        <div class="esqueleto h-5 w-1/2"></div>
                        <div class="mt-4 space-y-2.5">
                            <div class="esqueleto h-3 w-full"></div>
                            <div class="esqueleto h-3 w-11/12"></div>
                            <div class="esqueleto h-3 w-2/3"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif
</div>

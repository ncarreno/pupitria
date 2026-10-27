<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600,700|geist-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-stone-900 antialiased">
        <div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <div class="flex flex-col px-4 py-6 sm:px-10 lg:px-14">
                <x-marca href="/" />

                <div class="flex flex-1 items-center py-12">
                    <div class="mx-auto w-full max-w-sm animate-aparecer">
                        {{ $slot }}
                    </div>
                </div>

                <ul class="flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-stone-500">
                    <li class="inline-flex items-center gap-1.5"><x-icono nombre="escudo" class="size-4 text-pino-600" />Sin datos personales de alumnos</li>
                    <li class="inline-flex items-center gap-1.5"><x-icono nombre="candado" class="size-4 text-pino-600" />Cada docente ve solo lo suyo</li>
                </ul>
            </div>

            <aside class="relative m-3 hidden overflow-hidden rounded-[2rem] bg-pino-950 text-pino-50 lg:flex lg:flex-col lg:justify-between">
                <div aria-hidden="true" class="cuadricula absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent_85%)]"></div>
                <div aria-hidden="true" class="absolute -right-24 -top-24 size-[420px] rounded-full bg-pino-600/25 blur-3xl"></div>

                <div class="relative px-12 pt-14 xl:px-16">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pino-300">Piloto · Principado de Asturias</p>
                    <p class="mt-5 max-w-md text-balance text-3xl font-semibold leading-[1.15] tracking-tight text-white xl:text-[2.5rem]">Toda la clase trabaja lo mismo, cada uno a su nivel.</p>
                    <p class="mt-4 max-w-md text-[15px] leading-relaxed text-pino-100/75">Proyectos y fichas ajustados a tu grupo, con criterios comprobados con el Decreto 57/2022.</p>
                </div>

                {{-- Composición: la misma ficha en sus tres niveles --}}
                <div aria-hidden="true" class="relative mx-12 mb-12 mt-10 h-[300px] xl:mx-16">
                    @foreach ([
                        ['Refuerzo', 1, 'left-0 top-10 -rotate-6', 'animate-flotar-lento'],
                        ['Ampliación', 3, 'right-0 top-6 rotate-6', 'animate-flotar-lento'],
                        ['Estándar', 2, 'left-1/2 top-0 -translate-x-1/2', 'animate-flotar'],
                    ] as [$nivel, $puntos, $posicion, $animacion])
                        <div class="absolute {{ $posicion }} w-[58%] max-w-[260px]">
                            <div class="{{ $animacion }} rounded-2xl bg-white p-5 text-stone-800 shadow-[0_30px_60px_-20px_rgba(0,0,0,0.5)] ring-1 ring-black/5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-stone-400">{{ $nivel }}</span>
                                    <span class="flex gap-1">
                                        @foreach ([1, 2, 3] as $k)
                                            <i @class(['block size-2 rounded-full ring-[1.5px] ring-pino-600', 'bg-pino-600' => $k <= $puntos])></i>
                                        @endforeach
                                    </span>
                                </div>
                                <p class="mt-3 text-[15px] font-semibold tracking-tight text-stone-950">Animales de Oceanía</p>
                                <div class="mt-4 space-y-2">
                                    <div class="h-1.5 w-full rounded-full bg-stone-200"></div>
                                    <div class="h-1.5 w-5/6 rounded-full bg-stone-200"></div>
                                    @if ($puntos > 1)<div class="h-1.5 w-4/6 rounded-full bg-stone-200"></div>@endif
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="h-9 rounded-lg border border-dashed border-stone-300"></div>
                                    <div class="h-9 rounded-lg border border-dashed border-stone-300"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </body>
</html>

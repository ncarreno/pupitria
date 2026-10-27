<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fafaf9">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600,700|geist-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="relative isolate flex min-h-[100dvh] flex-col">
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[460px] bg-[radial-gradient(60%_100%_at_15%_0%,rgba(66,138,122,0.10),transparent_70%)]"></div>

            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="contenedor pb-8 pt-10 sm:pt-12">
                    {{ $header }}
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1 pb-20">
                {{ $slot }}
            </main>

            <footer class="border-t border-stone-200/80">
                <div class="contenedor flex flex-col gap-3 py-6 text-[13px] text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                    <ul class="flex flex-wrap gap-x-5 gap-y-2">
                        <li class="inline-flex items-center gap-1.5"><x-icono nombre="escudo" class="size-4 text-pino-600" />Sin datos personales de alumnos</li>
                        <li class="inline-flex items-center gap-1.5"><x-icono nombre="candado" class="size-4 text-pino-600" />Cada docente ve solo lo suyo</li>
                        <li class="inline-flex items-center gap-1.5"><x-icono nombre="libro" class="size-4 text-pino-600" />Currículo del Decreto 57/2022</li>
                    </ul>
                    <p>Piloto para Primaria · Principado de Asturias</p>
                </div>
            </footer>
        </div>
    </body>
</html>

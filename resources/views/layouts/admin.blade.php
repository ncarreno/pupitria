<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administración · {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600,700|geist-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-[100dvh] lg:grid lg:grid-cols-[272px_minmax(0,1fr)]">
            <livewire:layout.navegacion-admin />

            <div class="flex min-w-0 flex-col">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="contenedor pb-8 pt-8 lg:pt-12">
                        {{ $header }}
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1 pb-20">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

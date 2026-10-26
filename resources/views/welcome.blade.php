<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col">
            <header class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ url('/') }}" class="font-semibold text-gray-800">{{ config('app.name') }}</a>
                @if (Route::has('login'))
                    <livewire:welcome.navigation />
                @endif
            </header>

            <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-12 sm:py-20">
                <h1 class="text-3xl sm:text-4xl font-semibold leading-tight max-w-2xl">Proyectos y fichas para tu clase de Primaria, con el currículo de Asturias.</h1>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl">Escribe tu idea en una frase y la app prepara el diseño del proyecto con criterios reales del Decreto 57/2022. Las fichas salen en tres niveles (refuerzo, estándar y ampliación) para que toda la clase trabaje lo mismo a la vez.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Ir a mi panel</a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Crear cuenta</a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Entrar</a>
                    @endauth
                </div>

                <ul class="mt-14 grid sm:grid-cols-3 gap-4">
                    <li class="bg-white shadow-sm rounded-lg p-5">
                        <h2 class="font-semibold text-gray-900">Ajustado a tu clase</h2>
                        <p class="mt-1 text-sm text-gray-600">Curso, número de alumnos y áreas que impartes. No guardamos ningún dato de tus alumnos.</p>
                    </li>
                    <li class="bg-white shadow-sm rounded-lg p-5">
                        <h2 class="font-semibold text-gray-900">Currículo comprobado</h2>
                        <p class="mt-1 text-sm text-gray-600">Cada criterio y cada saber se comprueban con el decreto. Lo que la IA se invente se descarta.</p>
                    </li>
                    <li class="bg-white shadow-sm rounded-lg p-5">
                        <h2 class="font-semibold text-gray-900">Todo se puede editar</h2>
                        <p class="mt-1 text-sm text-gray-600">Cambias cualquier texto con un clic y pides ajustes cuando quieras. Las fichas se imprimen tal cual.</p>
                    </li>
                </ul>
            </main>

            <footer class="py-6 text-center text-sm text-gray-500">Piloto para docentes de Primaria del Principado de Asturias.</footer>
        </div>
    </body>
</html>

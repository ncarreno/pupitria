{{-- Página de impresión: solo el documento en A4. La barra de arriba no se imprime. --}}
@props(['titulo', 'volver'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $titulo }} · {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="impresion font-sans text-stone-900 antialiased">
        <div class="barra-impresion">
            <div class="contenedor flex flex-wrap items-center gap-3 py-3">
                <a href="{{ $volver }}" class="boton-secundario boton-sm"><x-icono nombre="flecha-izquierda" class="size-4" />Volver</a>
                <p class="flex-1 text-[13px] leading-snug text-stone-600">Para guardarlo como PDF, elige «Guardar como PDF» en lugar de una impresora. Si no salen las pautas o los colores, activa «Gráficos de fondo».</p>
                <button type="button" onclick="window.print()" class="boton-primario boton-sm"><x-icono nombre="imprimir" class="size-4" />Imprimir</button>
            </div>
        </div>

        <main class="hojas">
            {{ $slot }}
        </main>

        <script>
            document.fonts.ready.then(() => setTimeout(() => window.print(), 300));
        </script>
    </body>
</html>

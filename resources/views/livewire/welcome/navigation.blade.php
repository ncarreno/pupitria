<nav class="flex flex-1 items-center justify-end gap-1 text-sm">
    @auth
        <a href="{{ route('dashboard') }}" class="boton-primario boton-sm">
            Mi panel
            <x-icono nombre="flecha-derecha" class="size-4" />
        </a>
    @else
        <a href="{{ route('login') }}" class="boton-fantasma boton-sm">
            Entrar
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}" class="boton-oscuro boton-sm">
                Crear cuenta
            </a>
        @endif
    @endauth
</nav>

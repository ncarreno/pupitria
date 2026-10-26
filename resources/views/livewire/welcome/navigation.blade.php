<nav class="flex flex-1 justify-end gap-1 text-sm">
    @auth
        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-gray-700 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            Mi panel
        </a>
    @else
        <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-gray-700 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            Entrar
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}" class="rounded-md px-3 py-2 text-gray-700 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                Crear cuenta
            </a>
        @endif
    @endauth
</nav>

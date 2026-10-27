<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $enlaces = [
        ['ruta' => 'dashboard', 'activa' => 'dashboard', 'nombre' => 'Inicio', 'icono' => 'casa'],
        ['ruta' => 'clases', 'activa' => 'clases', 'nombre' => 'Mis clases', 'icono' => 'grupo'],
        ['ruta' => 'proyectos', 'activa' => 'proyectos*', 'nombre' => 'Proyectos', 'icono' => 'tablero'],
        ['ruta' => 'fichas', 'activa' => 'fichas*', 'nombre' => 'Fichas', 'icono' => 'ficha'],
        ['ruta' => 'curriculo', 'activa' => 'curriculo', 'nombre' => 'Currículo', 'icono' => 'libro'],
    ];
    $iniciales = str(auth()->user()->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-stone-200/70 bg-stone-50/80 backdrop-blur-xl backdrop-saturate-150">
    <!-- Primary Navigation Menu -->
    <div class="contenedor">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <x-marca :href="route('dashboard')" :subtitulo="null" class="shrink-0" />

                <!-- Navigation Links -->
                <div class="hidden items-center gap-1 lg:flex">
                    @foreach ($enlaces as $enlace)
                        <x-nav-link :href="route($enlace['ruta'])" :active="request()->routeIs($enlace['activa'])" :icono="$enlace['icono']" wire:navigate>
                            {{ $enlace['nombre'] }}
                        </x-nav-link>
                    @endforeach
                    @if (auth()->user()->esAdministrador())
                        <x-nav-link :href="route('admin.inicio')" icono="llave" wire:navigate>
                            Administración
                        </x-nav-link>
                    @elseif (auth()->user()->puedeValorarPruebas())
                        <x-nav-link :href="route('admin.pruebas')" :active="request()->routeIs('admin.pruebas*')" icono="estrella" wire:navigate>
                            Valorar pruebas
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('proyectos.nuevo') }}" wire:navigate class="boton-primario boton-sm hidden sm:inline-flex">
                    <x-icono nombre="mas" class="size-4" />
                    Nuevo proyecto
                </a>

                <!-- Settings Dropdown -->
                <div class="hidden lg:block">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-full py-1 pe-2 ps-1 text-sm font-medium text-stone-700 transition hover:bg-stone-200/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-pino-600" aria-label="Tu cuenta">
                                <span class="inline-flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-pino-100 to-pino-200 text-xs font-semibold uppercase text-pino-900 ring-1 ring-inset ring-pino-300/50">{{ $iniciales }}</span>
                                <span class="max-w-[10rem] truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <x-icono nombre="chevron-abajo" class="size-4 text-stone-400" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-stone-100 px-3 pb-2.5 pt-2">
                                <p class="truncate text-sm font-medium text-stone-950">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-stone-500">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="pt-1.5">
                                <x-dropdown-link :href="route('profile')" wire:navigate>
                                    <x-icono nombre="usuario" class="size-4 text-stone-400" />
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <!-- Authentication -->
                                <button wire:click="logout" class="w-full text-start">
                                    <x-dropdown-link>
                                        <x-icono nombre="salir" class="size-4 text-stone-400" />
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </button>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Hamburger -->
                <button @click="open = ! open" class="boton-icono size-10 lg:hidden" :aria-expanded="open" aria-label="Menú">
                    <x-icono nombre="menu" class="size-5" x-show="! open" />
                    <x-icono nombre="x" class="size-5" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="border-t border-stone-200/70 lg:hidden">
        <div class="contenedor space-y-1 py-3">
            @foreach ($enlaces as $enlace)
                <x-responsive-nav-link :href="route($enlace['ruta'])" :active="request()->routeIs($enlace['activa'])" :icono="$enlace['icono']" wire:navigate>
                    {{ $enlace['nombre'] }}
                </x-responsive-nav-link>
            @endforeach
            @if (auth()->user()->esAdministrador())
                <x-responsive-nav-link :href="route('admin.inicio')" icono="llave" wire:navigate>
                    Administración
                </x-responsive-nav-link>
            @elseif (auth()->user()->puedeValorarPruebas())
                <x-responsive-nav-link :href="route('admin.pruebas')" :active="request()->routeIs('admin.pruebas*')" icono="estrella" wire:navigate>
                    Valorar pruebas
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="contenedor border-t border-stone-200/70 py-4">
            <div class="flex items-center gap-3 px-3">
                <span class="inline-flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-pino-100 to-pino-200 text-sm font-semibold uppercase text-pino-900">{{ $iniciales }}</span>
                <div class="min-w-0">
                    <div class="truncate font-medium text-stone-950" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="truncate text-sm text-stone-500">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('proyectos.nuevo')" icono="mas" wire:navigate>
                    Nuevo proyecto
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile')" icono="usuario" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link icono="salir">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>

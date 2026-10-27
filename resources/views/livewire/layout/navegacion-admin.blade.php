<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

/**
 * Menú de la administración. Quien solo valora pruebas ve únicamente la batería.
 */
new class extends Component
{
    /**
     * @return list<array{ruta: string, activa: string, nombre: string, icono: string}>
     */
    public function enlaces(): array
    {
        $pruebas = ['ruta' => 'admin.pruebas', 'activa' => 'admin.pruebas*', 'nombre' => 'Batería de pruebas', 'icono' => 'matraz'];

        if (! auth()->user()->esAdministrador()) {
            return [$pruebas];
        }

        return [
            ['ruta' => 'admin.inicio', 'activa' => 'admin.inicio', 'nombre' => 'Resumen', 'icono' => 'casa'],
            ['ruta' => 'admin.ia', 'activa' => 'admin.ia', 'nombre' => 'Configuración de la IA', 'icono' => 'ajustes'],
            ['ruta' => 'admin.ia.uso', 'activa' => 'admin.ia.uso', 'nombre' => 'Uso de la IA', 'icono' => 'grafico'],
            $pruebas,
            ['ruta' => 'admin.cuentas', 'activa' => 'admin.cuentas', 'nombre' => 'Cuentas', 'icono' => 'grupo'],
        ];
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirectRoute('admin.login', navigate: true);
    }
}; ?>

@php $iniciales = str(auth()->user()->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''); @endphp

<nav x-data="{ open: false }" class="relative z-40 bg-stone-900 text-stone-300 lg:sticky lg:top-0 lg:flex lg:h-[100dvh] lg:flex-col">
    <div aria-hidden="true" class="cuadricula pointer-events-none absolute inset-0 opacity-60 [mask-image:linear-gradient(to_bottom,black,transparent_40%)]"></div>

    <div class="relative flex h-16 items-center justify-between px-4 sm:px-6 lg:h-auto lg:px-5 lg:pb-6 lg:pt-6">
        <x-marca :href="route('admin.inicio')" oscura subtitulo="Administración" />

        <button @click="open = ! open" class="inline-flex size-10 items-center justify-center rounded-lg text-stone-400 transition hover:bg-white/5 hover:text-white lg:hidden" :aria-expanded="open" aria-label="Menú">
            <x-icono nombre="menu" class="size-5" x-show="! open" />
            <x-icono nombre="x" class="size-5" x-show="open" x-cloak />
        </button>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="relative hidden border-t border-white/5 lg:flex lg:flex-1 lg:flex-col lg:border-0">
        <div class="space-y-1 px-3 py-3 lg:flex-1 lg:py-0">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-stone-500">Panel</p>
            @foreach ($this->enlaces() as $enlace)
                @php $activa = request()->routeIs($enlace['activa']); @endphp
                <a href="{{ route($enlace['ruta']) }}" wire:navigate @if ($activa) aria-current="page" @endif
                    @class([
                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-200 ease-suave',
                        'bg-white/[0.07] text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] ring-1 ring-white/10' => $activa,
                        'text-stone-400 hover:bg-white/[0.04] hover:text-white' => ! $activa,
                    ])>
                    <x-icono :nombre="$enlace['icono']" @class(['size-[18px]', 'text-pino-400' => $activa, 'text-stone-500 group-hover:text-stone-300' => ! $activa]) />
                    {{ $enlace['nombre'] }}
                </a>
            @endforeach
        </div>

        <div class="space-y-1 border-t border-white/5 px-3 py-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-stone-400 transition hover:bg-white/[0.04] hover:text-white">
                <x-icono nombre="flecha-izquierda" class="size-[18px] text-stone-500" />
                Ir a la app
            </a>
            <div class="flex items-center gap-3 rounded-xl px-3 py-2">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-pino-900 text-xs font-semibold uppercase text-pino-200 ring-1 ring-inset ring-pino-700">{{ $iniciales }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-stone-500">{{ auth()->user()->email }}</p>
                </div>
                <button wire:click="logout" class="inline-flex size-8 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white/5 hover:text-white" title="{{ __('Log Out') }}">
                    <x-icono nombre="salir" class="size-[18px]" />
                    <span class="sr-only">{{ __('Log Out') }}</span>
                </button>
            </div>
        </div>
    </div>
</nav>

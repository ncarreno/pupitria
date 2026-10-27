<?php

use App\View\EstilosDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $estilo = '';

    public function mount(): void
    {
        $this->estilo = EstilosDocumento::resolver(null, Auth::user());
    }

    /**
     * Guarda el estilo por defecto de la docente en `users.preferencias`. Lo usan sus proyectos y fichas sin estilo propio.
     */
    public function guardarEstilo(): void
    {
        $this->validate([
            'estilo' => ['required', Rule::in(array_keys(EstilosDocumento::ESTILOS))],
        ]);

        $user = Auth::user();
        $user->forceFill(['preferencias' => array_merge($user->preferencias ?? [], ['estilo' => $this->estilo])])->save();

        $this->dispatch('estilo-guardado');
    }
}; ?>

<section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.7fr)] lg:gap-12">
    <header>
        <h2 class="text-lg font-semibold tracking-tight text-stone-950">Estilo de tus documentos</h2>

        <p class="mt-1 text-sm leading-relaxed text-stone-600">
            Cómo se ven e imprimen tus fichas y proyectos. Cambia solo el aspecto, no el contenido. En cada documento puedes elegir otro.
        </p>
    </header>

    <form wire:submit="guardarEstilo" class="max-w-xl space-y-5">
        <link rel="preconnect" href="https://fonts.bunny.net">
        @foreach (EstilosDocumento::ESTILOS as $clave => $datos)
            <link href="{{ EstilosDocumento::urlFuentes($clave) }}" rel="stylesheet" />
        @endforeach

        <fieldset class="grid gap-3 sm:grid-cols-2">
            <legend class="sr-only">Estilo</legend>
            @foreach (EstilosDocumento::ESTILOS as $clave => $datos)
                @php $elegido = $estilo === $clave; @endphp
                <label @class(['opcion flex-col', 'opcion-activa' => $elegido, 'opcion-inactiva' => ! $elegido])>
                    <input type="radio" wire:model.live="estilo" value="{{ $clave }}" class="sr-only">
                    <span class="flex items-center justify-between">
                        <span class="t-{{ $clave }} text-2xl leading-none" style="font-family: var(--f-titulo); color: var(--tinta)">Aa</span>
                        <span @class(['inline-flex size-5 items-center justify-center rounded-full ring-1 ring-inset transition', 'bg-pino-700 ring-pino-700 text-white' => $elegido, 'ring-stone-300' => ! $elegido])>@if ($elegido)<x-icono nombre="check" class="size-3" />@endif</span>
                    </span>
                    <span>
                        <span class="block font-medium text-stone-950">{{ $datos['nombre'] }}</span>
                        <span class="mt-0.5 block text-[13px] leading-snug text-stone-500">{{ $datos['descripcion'] }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>
        <x-input-error :messages="$errors->get('estilo')" />

        <div class="flex items-center gap-4 pt-1">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="estilo-guardado">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>

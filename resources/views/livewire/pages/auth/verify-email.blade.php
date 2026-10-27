<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="relative inline-flex">
        <span class="absolute inset-0 animate-latido rounded-2xl bg-pino-200"></span>
        <span class="relative inline-flex size-14 items-center justify-center rounded-2xl bg-pino-700 text-white shadow-suave"><x-icono nombre="correo" class="size-6" /></span>
    </div>
    <h1 class="mt-7 text-3xl font-semibold tracking-tight text-stone-950">Confirma tu email</h1>
    <p class="mt-3 text-[15px] leading-relaxed text-stone-600">
        Gracias por registrarte. Antes de empezar, confirma tu email con el enlace que te acabamos de enviar. Si no te ha llegado, mira en la carpeta de spam o pide otro.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="aviso-pino mt-6">
            <x-icono nombre="check-circulo" class="size-5 text-pino-700" />
            <span class="font-medium">Te hemos enviado un enlace nuevo al email con el que te registraste.</span>
        </div>
    @endif

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <x-primary-button wire:click="sendVerification" wire:loading.attr="disabled">
            <x-icono nombre="refrescar" class="size-4" />
            Enviar otro enlace
        </x-primary-button>

        <button wire:click="logout" type="submit" class="enlace text-sm text-stone-600">
            Cerrar sesión
        </button>
    </div>
</div>

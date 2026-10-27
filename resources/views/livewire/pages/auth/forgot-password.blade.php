<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <span class="inline-flex size-11 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="llave" class="size-5" /></span>
    <h1 class="mt-6 text-3xl font-semibold tracking-tight text-stone-950">Recupera el acceso</h1>
    <p class="mt-2 text-[15px] leading-relaxed text-stone-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="mt-8 space-y-5">
        <!-- Email Address -->
        <div class="grid gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full py-3" wire:loading.attr="disabled">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>
    </form>

    <p class="mt-8 text-sm"><a href="{{ route('login') }}" wire:navigate class="enlace"><x-icono nombre="flecha-izquierda" class="size-4" />Volver a entrar</a></p>
</div>

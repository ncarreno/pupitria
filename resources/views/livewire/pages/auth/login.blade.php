<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-3xl font-semibold tracking-tight text-stone-950">Hola de nuevo</h1>
    <p class="mt-2 text-[15px] text-stone-600">Entra para seguir con tus proyectos y fichas.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form wire:submit="login" class="mt-8 space-y-5">
        <!-- Email Address -->
        <div class="grid gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" placeholder="nombre@educastur.org" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <!-- Password -->
        <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="enlace text-[13px]" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input wire:model="form.password" id="password"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="inline-flex cursor-pointer items-center gap-2.5">
            <input wire:model="form.remember" id="remember" type="checkbox" class="casilla" name="remember">
            <span class="text-sm text-stone-600">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">Entrando…</span>
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-sm text-stone-600">¿Todavía no tienes cuenta? <a href="{{ route('register') }}" wire:navigate class="enlace">Crear cuenta</a></p>
    @endif
</div>

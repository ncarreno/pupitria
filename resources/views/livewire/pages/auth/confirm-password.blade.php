<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <span class="inline-flex size-11 items-center justify-center rounded-xl bg-pino-50 text-pino-700 ring-1 ring-inset ring-pino-200"><x-icono nombre="candado" class="size-5" /></span>
    <h1 class="mt-6 text-3xl font-semibold tracking-tight text-stone-950">Confirma que eres tú</h1>
    <p class="mt-2 text-[15px] leading-relaxed text-stone-600">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form wire:submit="confirmPassword" class="mt-8 space-y-5">
        <!-- Password -->
        <div class="grid gap-2">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password"
                          id="password"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full py-3">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</div>

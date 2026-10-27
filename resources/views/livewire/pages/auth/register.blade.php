<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-3xl font-semibold tracking-tight text-stone-950">Crea tu cuenta</h1>
    <p class="mt-2 text-[15px] text-stone-600">Solo te pedimos tu nombre y tu email. Nada de tus alumnos.</p>

    <form wire:submit="register" class="mt-8 space-y-5">
        <!-- Name -->
        <div class="grid gap-2">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div class="grid gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="grid gap-2">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="grid gap-2">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full py-3" wire:loading.attr="disabled">
            {{ __('Register') }}
        </x-primary-button>

        <p class="flex items-start gap-2 text-[13px] leading-relaxed text-stone-500">
            <x-icono nombre="correo" class="mt-0.5 size-4 text-stone-400" />
            Te enviaremos un enlace para confirmar tu email antes de empezar.
        </p>
    </form>

    <p class="mt-8 text-sm text-stone-600">
        <a class="enlace" href="{{ route('login') }}" wire:navigate>
            {{ __('Already registered?') }}
        </a>
    </p>
</div>

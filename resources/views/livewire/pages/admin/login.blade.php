<?php

use App\Livewire\Forms\LoginForm;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Entrada a la administración, aparte del login de las docentes. Solo deja entrar a quien
 * administra o valora pruebas; si ya hay una sesión de docente, entrar aquí la sustituye.
 */
new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function mount(): void
    {
        if (auth()->user()?->esAdministrador()) {
            $this->redirectRoute('admin.inicio', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate(fn (User $user) => $user->puedeEntrarEnAdministracion());

        Session::regenerate();

        $inicio = auth()->user()->esAdministrador() ? 'admin.inicio' : 'admin.pruebas';

        $this->redirectIntended(default: route($inicio, absolute: false), navigate: true);
    }
}; ?>

<div>
    <span class="inline-flex size-11 items-center justify-center rounded-xl bg-stone-900 text-pino-300 shadow-suave"><x-icono nombre="escudo" class="size-5" /></span>
    <h1 class="mt-6 text-3xl font-semibold tracking-tight text-stone-950">Administración</h1>
    <p class="mt-2 text-[15px] text-stone-600">Acceso para quien administra la app o valora la batería de pruebas.</p>

    @auth
        <div class="aviso-ambar mt-6">
            <x-icono nombre="info" class="size-5 text-amber-700" />
            <p>
                Has entrado en la app como {{ auth()->user()->email }}, que no tiene acceso a esta parte.
                Entra con una cuenta de administrador o <a href="{{ route('dashboard') }}" wire:navigate class="font-medium underline underline-offset-4">vuelve a la app</a>.
            </p>
        </div>
    @endauth

    <form wire:submit="login" class="mt-8 space-y-5">
        <div class="grid gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <div class="grid gap-2">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" />
        </div>

        <label for="remember" class="inline-flex cursor-pointer items-center gap-2.5">
            <input wire:model="form.remember" id="remember" type="checkbox" class="casilla" name="remember">
            <span class="text-sm text-stone-600">{{ __('Remember me') }}</span>
        </label>

        <button type="submit" class="boton-oscuro w-full py-3">
            {{ __('Log in') }}
        </button>
    </form>
</div>

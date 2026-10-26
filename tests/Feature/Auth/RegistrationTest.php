<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_los_errores_del_registro_salen_en_espanol_con_el_nombre_del_campo(): void
    {
        Volt::test('pages.auth.register')
            ->set('email', 'no-es-un-email')
            ->set('password', 'password')
            ->set('password_confirmation', 'otra')
            ->call('register')
            ->assertHasErrors([
                'name' => 'El campo nombre es obligatorio.',
                'email' => 'Escribe un email válido.',
                'password' => 'La confirmación de contraseña no coincide.',
            ]);

        $this->assertGuest();
    }
}

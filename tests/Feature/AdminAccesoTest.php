<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_la_administracion_lleva_a_su_propia_entrada(): void
    {
        $this->get(route('admin.inicio'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.pruebas'))->assertRedirect(route('admin.login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_el_administrador_entra_y_vuelve_a_la_pagina_que_pidio(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::test('pages.admin.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.inicio', absolute: false));
        $this->assertAuthenticatedAs($admin);

        auth()->logout();
        $this->get(route('admin.ia'));

        Volt::test('pages.admin.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.ia'));
    }

    public function test_una_docente_no_entra_por_la_administracion(): void
    {
        $docente = User::factory()->create();

        Volt::test('pages.admin.login')
            ->set('form.email', $docente->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email' => 'El email o la contraseña no son correctos.'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_quien_valora_entra_y_solo_ve_la_bateria(): void
    {
        $valoradora = User::factory()->valoradora()->create();

        Volt::test('pages.admin.login')
            ->set('form.email', $valoradora->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.pruebas', absolute: false));
        $this->assertAuthenticatedAs($valoradora);

        $this->get(route('admin.pruebas'))->assertOk()->assertDontSee('Configuración de la IA');
        $this->get(route('admin.inicio'))->assertRedirect(route('admin.login'));
    }

    public function test_con_sesion_de_docente_pide_entrar_con_una_cuenta_de_administrador(): void
    {
        $docente = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($docente)->get(route('admin.login'))
            ->assertOk()
            ->assertSee("Has entrado en la app como {$docente->email}, que no tiene acceso a esta parte.");

        Volt::actingAs($docente)->test('pages.admin.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.inicio', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_el_administrador_con_sesion_salta_la_entrada(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.login'))
            ->assertRedirect(route('admin.inicio'));
    }

    public function test_el_resumen_da_recuentos_sin_el_contenido_y_solo_lo_ve_el_administrador(): void
    {
        Proyecto::factory()->create(['titulo' => 'Proyecto secreto del agua']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.inicio'))
            ->assertOk()
            ->assertSee('Resumen')
            ->assertDontSee('Proyecto secreto del agua');

        $cifras = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.inicio')->instance()->cifras;
        $this->assertSame(3, $cifras['Cuentas'][0]['valor']);
        $this->assertSame(1, $cifras['Contenido'][1]['valor']);

        Volt::actingAs(User::factory()->create())->test('pages.admin.inicio')->assertForbidden();
    }

    public function test_cerrar_sesion_en_la_administracion_vuelve_a_su_entrada(): void
    {
        Volt::actingAs(User::factory()->admin()->create())->test('layout.navegacion-admin')
            ->call('logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}

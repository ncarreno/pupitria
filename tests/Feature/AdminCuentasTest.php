<?php

namespace Tests\Feature;

use App\Models\Ficha;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminCuentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_el_administrador_ve_las_cuentas(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.cuentas'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->valoradora()->create())->get(route('admin.cuentas'))->assertRedirect(route('admin.login'));
        Volt::actingAs(User::factory()->create())->test('pages.admin.cuentas')->assertForbidden();
    }

    public function test_muestra_recuentos_pero_no_el_contenido_de_cada_docente(): void
    {
        $proyecto = Proyecto::factory()->create(['titulo' => 'Proyecto secreto del agua']);
        Ficha::factory()->for($proyecto->clase)->create(['user_id' => $proyecto->user_id, 'titulo' => 'Ficha secreta del koala']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.cuentas'))
            ->assertOk()
            ->assertSee($proyecto->user->email)
            ->assertDontSee('Proyecto secreto del agua')
            ->assertDontSee('Ficha secreta del koala');

        $docente = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->instance()->cuentas->firstWhere('id', $proyecto->user_id);
        $this->assertSame([1, 1, 1], [$docente->clases_count, $docente->proyectos_count, $docente->fichas_count]);
    }

    public function test_busca_por_nombre_o_email(): void
    {
        User::factory()->create(['name' => 'Lucía Fernández', 'email' => 'lucia@colegio.es']);
        User::factory()->create(['name' => 'Marta Suárez', 'email' => 'marta@colegio.es']);

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->set('buscar', 'lucía')
            ->assertSee('lucia@colegio.es')
            ->assertDontSee('marta@colegio.es')
            ->set('buscar', 'marta@')
            ->assertSee('Marta Suárez')
            ->assertDontSee('Lucía Fernández');
    }

    public function test_da_y_quita_los_permisos_de_administrar_y_valorar(): void
    {
        $docente = User::factory()->create();

        $panel = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->call('alternarAdministrador', $docente->id)
            ->call('alternarValorar', $docente->id);
        $this->assertTrue($docente->fresh()->esAdministrador());
        $this->assertTrue($docente->fresh()->puede_valorar);

        $panel->call('alternarAdministrador', $docente->id)->call('alternarValorar', $docente->id);
        $this->assertSame('docente', $docente->fresh()->rol);
        $this->assertFalse($docente->fresh()->puede_valorar);
    }

    public function test_el_administrador_no_puede_quitarse_su_propio_rol(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)->test('pages.admin.cuentas')
            ->call('alternarAdministrador', $admin->id)
            ->assertHasErrors(["cuenta.{$admin->id}" => 'No puedes quitarte a ti mismo el rol de administrador.']);

        $this->assertTrue($admin->fresh()->esAdministrador());
    }

    public function test_marca_verificado_el_email_de_una_cuenta(): void
    {
        $docente = User::factory()->unverified()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->call('marcarVerificada', $docente->id);

        $this->assertTrue($docente->fresh()->hasVerifiedEmail());
    }

    public function test_el_administrador_da_de_alta_una_cuenta_verificada(): void
    {
        $panel = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->call('nueva')
            ->assertSet('mostrarFormulario', true)
            ->set('nombre', 'Lucía Fernández')
            ->set('email', ' Lucia@Colegio.es ')
            ->set('password', 'clave-de-prueba')
            ->set('valorar', true)
            ->call('crear')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false)
            ->assertSee('clave-de-prueba');

        $cuenta = User::firstWhere('email', 'lucia@colegio.es');
        $this->assertSame('Lucía Fernández', $cuenta->name);
        $this->assertTrue($cuenta->hasVerifiedEmail());
        $this->assertTrue(Hash::check('clave-de-prueba', $cuenta->password));
        $this->assertSame('docente', $cuenta->rol);
        $this->assertTrue($cuenta->puede_valorar);

        $panel->call('nueva')->assertSet('creada', null)->set('nombre', 'Marta')->set('email', 'marta@colegio.es')
            ->set('administrador', true)->call('crear');
        $this->assertTrue(User::firstWhere('email', 'marta@colegio.es')->esAdministrador());
    }

    public function test_el_alta_valida_el_email_repetido_y_la_contrasena(): void
    {
        User::factory()->create(['email' => 'lucia@colegio.es']);

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->call('nueva')
            ->set('nombre', 'Lucía')
            ->set('email', 'LUCIA@colegio.es')
            ->set('password', 'corta')
            ->call('crear')
            ->assertHasErrors(['email' => 'unique', 'password']);

        $this->assertSame(2, User::count());
    }
}

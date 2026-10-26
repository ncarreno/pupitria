<?php

namespace Tests\Feature;

use App\Models\Generacion;
use App\Models\IaConfiguracion;
use App\Models\User;
use App\Services\IA\ClienteIA;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\StructuredAnonymousAgent;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminIATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IaConfiguracionSeeder::class);
    }

    private function fila(string $tarea, string $proveedor): IaConfiguracion
    {
        return IaConfiguracion::where('tarea', $tarea)->where('proveedor', $proveedor)->sole();
    }

    public function test_solo_el_administrador_entra_en_el_panel_y_ve_el_enlace(): void
    {
        $docente = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($docente)->get(route('admin.ia'))->assertForbidden();
        $this->actingAs($docente)->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.ia'));

        $this->actingAs($admin)->get(route('admin.ia'))->assertOk()->assertSee('Ajustes de proyectos');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee(route('admin.ia'));
    }

    public function test_una_docente_no_puede_usar_las_acciones_del_panel(): void
    {
        Volt::actingAs(User::factory()->create())->test('pages.admin.ia')->assertForbidden();
    }

    public function test_anadir_y_subir_un_proveedor_lo_convierte_en_el_principal(): void
    {
        StructuredAnonymousAgent::fake([['titulo' => 'Ríos de Asturias']]);

        $panel = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia')
            ->set('nuevo.prueba', 'openai')
            ->call('anadir', 'prueba')
            ->assertHasNoErrors();
        $panel->call('mover', $this->fila('prueba', 'openai')->id, -1);

        $this->assertSame(['openai', 'anthropic'], IaConfiguracion::paraTarea('prueba')->pluck('proveedor')->all());
        $r = app(ClienteIA::class)->generarEstructurado('prueba', 'x', 'y', fn (JsonSchema $s) => ['titulo' => $s->string()->required()]);
        $this->assertSame('openai', $r['generacion']->proveedor);
    }

    public function test_no_se_puede_dejar_una_tarea_sin_proveedor_activo(): void
    {
        $unica = $this->fila('ficha', 'anthropic');

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia')
            ->call('alternar', $unica->id)
            ->assertHasErrors(['tarea.ficha' => 'Cada tarea necesita al menos un proveedor activo.'])
            ->call('quitar', $unica->id)
            ->assertHasErrors(['tarea.ficha' => 'Cada tarea necesita al menos un proveedor activo.']);

        $this->assertTrue($unica->fresh()->activo);
    }

    public function test_en_ilustraciones_solo_se_anaden_proveedores_que_generan_imagenes(): void
    {
        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia')
            ->set('nuevo.imagen', 'anthropic')
            ->call('anadir', 'imagen')
            ->assertHasErrors(['nuevo.imagen' => 'Elige un proveedor de la lista.']);

        $this->assertSame(['openai'], IaConfiguracion::where('tarea', 'imagen')->pluck('proveedor')->all());
    }

    public function test_guarda_modelo_y_tiempo_dentro_del_limite_de_la_tarea(): void
    {
        $fila = $this->fila('proyecto', 'anthropic');

        $panel = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia')
            ->set("modelos.{$fila->id}", 'claude-sonnet-5-5')
            ->set("timeouts.{$fila->id}", 900)
            ->call('guardar', $fila->id)
            ->assertHasErrors(["timeouts.{$fila->id}" => 'max']);
        $this->assertNull($fila->fresh()->modelo);

        $panel->set("timeouts.{$fila->id}", 200)->call('guardar', $fila->id)->assertHasNoErrors();
        $this->assertSame('claude-sonnet-5-5', $fila->fresh()->modelo);
        $this->assertSame(200, $fila->fresh()->timeout);
    }

    public function test_probar_usa_solo_el_proveedor_de_la_fila(): void
    {
        StructuredAnonymousAgent::fake(fn ($prompt, $adjuntos, $proveedor) => ['titulo' => 'Responde '.$proveedor->name()]);
        $admin = User::factory()->admin()->create();
        $respaldo = IaConfiguracion::create(['tarea' => 'ficha', 'proveedor' => 'openai', 'orden' => 2, 'timeout' => 180, 'activo' => true]);

        Volt::actingAs($admin)->test('pages.admin.ia')
            ->call('probar', $respaldo->id)
            ->assertSet('prueba.ok', true)
            ->assertSee('Responde openai');

        $generacion = Generacion::sole();
        $this->assertSame('openai', $generacion->proveedor);
        $this->assertSame('prueba', $generacion->tarea);
        $this->assertSame($admin->id, $generacion->user_id);
    }
}

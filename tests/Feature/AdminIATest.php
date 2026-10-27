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

        $this->actingAs($docente)->get(route('admin.ia'))->assertRedirect(route('admin.login'));
        $this->actingAs($docente)->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.inicio'));

        $this->actingAs($admin)->get(route('admin.ia'))->assertOk()->assertSee('Ajustes de proyectos');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee(route('admin.inicio'));
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

    public function test_el_panel_ofrece_los_modelos_del_proveedor_y_se_usa_el_elegido(): void
    {
        $fila = $this->fila('prueba', 'anthropic');
        $this->assertSame(
            ['claude-haiku-5-5' => 'económico', 'claude-sonnet-5-5' => 'por defecto', 'claude-opus-5-5' => 'el más potente'],
            IaConfiguracion::modelosPara('prueba', 'anthropic'),
        );

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia')
            ->assertSee('Por defecto (claude-sonnet-5-5)')
            ->assertSee('claude-haiku-5-5 · económico')
            ->set("modelos.{$fila->id}", 'claude-haiku-5-5')
            ->call('guardar', $fila->id)
            ->assertHasNoErrors();

        StructuredAnonymousAgent::fake([['titulo' => 'Ríos de Asturias']]);
        $r = app(ClienteIA::class)->generarEstructurado('prueba', 'x', 'y', fn (JsonSchema $s) => ['titulo' => $s->string()->required()]);
        $this->assertSame('claude-haiku-5-5', $r['generacion']->modelo);
    }

    public function test_solo_el_administrador_ve_el_uso(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.ia.uso'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.ia.uso'))->assertOk()->assertSee('Uso de la IA');
    }

    public function test_el_uso_resume_por_tarea_y_proveedor_dentro_del_periodo(): void
    {
        $generacion = fn (array $datos, int $haceDias = 0) => Generacion::create(['tarea' => 'proyecto', 'estado' => 'completada', ...$datos])
            ->forceFill(['created_at' => now()->subDays($haceDias)])->save();
        $generacion(['proveedor' => 'anthropic', 'modelo' => 'claude-x', 'duracion_ms' => 1000, 'tokens_entrada' => 12000]);
        $generacion(['proveedor' => 'anthropic', 'modelo' => 'claude-x', 'duracion_ms' => 3000, 'tokens_entrada' => 345]);
        $generacion(['proveedor' => 'openai', 'modelo' => 'gpt-x', 'respaldo' => true, 'duracion_ms' => 5000, 'tokens_entrada' => 10]);
        $generacion(['estado' => 'fallida', 'error' => 'RuntimeException: Proveedor caído']);
        $generacion(['proveedor' => 'anthropic', 'modelo' => 'claude-x', 'duracion_ms' => 1000, 'tokens_entrada' => 900000], haceDias: 10);

        $uso = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.ia-uso')
            ->assertSee('12.345')
            ->assertSee('2,0 s')
            ->assertSee('25 % fallidas')
            ->assertSee('33 % por el respaldo')
            ->assertSee('Proveedor caído')
            ->assertDontSee('912.345');

        $uso->set('dias', 30)->assertSee('912.345');
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

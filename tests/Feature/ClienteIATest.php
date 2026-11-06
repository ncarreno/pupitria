<?php

namespace Tests\Feature;

use App\Models\Generacion;
use App\Models\IaConfiguracion;
use App\Models\User;
use App\Services\IA\AgenteEstructurado;
use App\Services\IA\ClienteIA;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Tests\TestCase;

class ClienteIATest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_datos_estructurados_y_registra_la_generacion(): void
    {
        $this->seed(IaConfiguracionSeeder::class);
        AgenteEstructurado::fake([['titulo' => 'Ríos de Asturias', 'lema' => 'Del Nalón al mar']]);

        $r = app(ClienteIA::class)->generarEstructurado(
            tarea: 'prueba',
            instrucciones: 'Instrucciones',
            peticion: 'Petición',
            esquema: fn (JsonSchema $s) => ['titulo' => $s->string()->required(), 'lema' => $s->string()->required()],
        );

        $this->assertSame('Ríos de Asturias', $r['datos']['titulo']);
        $this->assertSame('completada', $r['generacion']->estado);
        $this->assertSame(1, Generacion::count());
    }

    public function test_si_el_principal_falla_responde_el_respaldo_y_queda_registrado(): void
    {
        $this->seed(IaConfiguracionSeeder::class);
        IaConfiguracion::create(['tarea' => 'prueba', 'proveedor' => 'openai', 'orden' => 2, 'timeout' => 60, 'activo' => true]);
        AgenteEstructurado::fake(fn ($prompt, $adjuntos, $proveedor) => $proveedor->name() === 'anthropic'
            ? throw RateLimitedException::forProvider('anthropic')
            : ['titulo' => 'Ríos de Asturias']);

        $r = app(ClienteIA::class)->generarEstructurado('prueba', 'x', 'y', fn (JsonSchema $s) => ['titulo' => $s->string()->required()]);

        $this->assertSame('Ríos de Asturias', $r['datos']['titulo']);
        $this->assertSame('openai', $r['generacion']->proveedor);
        $this->assertTrue($r['generacion']->respaldo);
        $this->assertSame('completada', $r['generacion']->estado);
    }

    /**
     * Sin el punto de corte, Anthropic no cachea nada y el currículo se paga entero en cada llamada.
     */
    public function test_con_anthropic_cachea_las_instrucciones_y_registra_los_tokens_de_cache(): void
    {
        $this->seed(IaConfiguracionSeeder::class);
        Http::preventStrayRequests();
        Http::fake(['api.anthropic.com/*' => Http::response([
            'id' => 'msg_1',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-sonnet-5-5',
            'content' => [['type' => 'text', 'text' => '{"titulo":"Ríos de Asturias"}']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 120, 'output_tokens' => 40, 'cache_creation_input_tokens' => 3000, 'cache_read_input_tokens' => 9000],
        ])]);

        $r = app(ClienteIA::class)->generarEstructurado('prueba', 'Currículo', 'Idea', fn (JsonSchema $s) => ['titulo' => $s->string()->required()]);

        Http::assertSent(fn ($peticion) => data_get($peticion->data(), 'system.0.text') === 'Currículo'
            && data_get($peticion->data(), 'system.0.cache_control.type') === 'ephemeral');
        $this->assertSame('Ríos de Asturias', $r['datos']['titulo']);
        $this->assertSame(9000, $r['generacion']->tokens_cache);
        $this->assertSame(3000, $r['generacion']->tokens_cache_escritos);
    }

    public function test_falla_si_no_hay_proveedor_para_la_tarea(): void
    {
        $this->expectException(RuntimeException::class);

        app(ClienteIA::class)->generarEstructurado('inexistente', 'x', 'y', fn (JsonSchema $s) => []);
    }

    public function test_solo_el_administrador_pasa_el_middleware_admin(): void
    {
        Route::middleware(['web', 'auth', 'admin'])->get('/_prueba_admin', fn () => 'ok');

        $docente = User::factory()->create();
        $this->actingAs($docente)->get('/_prueba_admin')->assertRedirect(route('admin.login'));

        $docente->forceFill(['rol' => 'admin'])->save();
        $this->actingAs($docente->fresh())->get('/_prueba_admin')->assertOk();
    }
}

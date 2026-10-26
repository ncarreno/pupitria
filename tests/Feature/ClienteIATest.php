<?php

namespace Tests\Feature;

use App\Models\Generacion;
use App\Models\IaConfiguracion;
use App\Models\User;
use App\Services\IA\ClienteIA;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\StructuredAnonymousAgent;
use RuntimeException;
use Tests\TestCase;

class ClienteIATest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_datos_estructurados_y_registra_la_generacion(): void
    {
        $this->seed(IaConfiguracionSeeder::class);
        StructuredAnonymousAgent::fake([['titulo' => 'Ríos de Asturias', 'lema' => 'Del Nalón al mar']]);

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
        StructuredAnonymousAgent::fake(fn ($prompt, $adjuntos, $proveedor) => $proveedor->name() === 'anthropic'
            ? throw RateLimitedException::forProvider('anthropic')
            : ['titulo' => 'Ríos de Asturias']);

        $r = app(ClienteIA::class)->generarEstructurado('prueba', 'x', 'y', fn (JsonSchema $s) => ['titulo' => $s->string()->required()]);

        $this->assertSame('Ríos de Asturias', $r['datos']['titulo']);
        $this->assertSame('openai', $r['generacion']->proveedor);
        $this->assertTrue($r['generacion']->respaldo);
        $this->assertSame('completada', $r['generacion']->estado);
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
        $this->actingAs($docente)->get('/_prueba_admin')->assertForbidden();

        $docente->forceFill(['rol' => 'admin'])->save();
        $this->actingAs($docente->fresh())->get('/_prueba_admin')->assertOk();
    }
}

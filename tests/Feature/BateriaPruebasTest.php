<?php

namespace Tests\Feature;

use App\Models\Ficha;
use App\Models\Generacion;
use App\Models\IaCaso;
use App\Models\IaEjecucion;
use App\Models\Proyecto;
use App\Models\User;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\StructuredAnonymousAgent;
use Livewire\Volt\Volt;
use RuntimeException;
use Tests\TestCase;

class BateriaPruebasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
        $this->seed(IaConfiguracionSeeder::class);
    }

    /**
     * Respuesta de la IA para un proyecto, con un criterio inventado (CN.C9.9.9).
     *
     * @return array<string, mixed>
     */
    private function respuestaProyecto(string $titulo = 'Exploradores del planeta'): array
    {
        return [
            'titulo' => $titulo, 'lema' => 'Una expedición', 'temporalizacion' => '6 semanas',
            'narracion' => ['secuencia' => 'Llega un baúl.', 'vinculacion' => 'Les gustan los animales.'],
            'curriculo' => ['criterios' => ['LCL.C2.4.1', 'CN.C9.9.9'], 'saberes' => ['CN.C2.A.2.4']],
            'ods' => ['desafio' => 'Cuidar los ecosistemas.', 'lista' => [15]],
            'producto_final' => 'Museo de los continentes.',
            'tareas' => [['titulo' => 'Abrimos el baúl', 'descripcion' => 'Leemos el cuaderno.', 'areas' => ['LCL'], 'criterios' => ['LCL.C2.4.1']]],
            'agrupamientos' => 'Equipos.', 'espacios' => 'Aula.', 'metodologias' => [['nombre' => 'ABP', 'descripcion' => 'Museo.']],
            'recursos' => 'Mapas.', 'tic' => 'Mapas digitales.', 'evaluacion' => [['instrumento' => 'Rúbrica', 'que' => 'Diario.']],
            'difusion' => ['Visita de 1.º'], 'itinerario' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function respuestaFicha(): array
    {
        $nivel = ['consigna' => 'Lee y responde.', 'bloques' => [[
            'tipo' => 'lectura', 'titulo' => '¿Quién es el koala?', 'enunciado' => '', 'texto' => 'El koala vive en Australia.', 'preguntas' => [], 'lineas' => 2,
            'items' => [], 'frases' => [], 'banco' => [], 'columnas' => [], 'filas' => [], 'izquierda' => [], 'derecha' => [],
        ]]];

        return ['titulo' => 'El koala', 'criterios' => ['CN.C2.5.1', 'CN.C9.9.9'], 'ilustracion' => '', 'niveles' => [['nivel' => 'refuerzo'] + $nivel, ['nivel' => 'estandar'] + $nivel, ['nivel' => 'ampliacion'] + $nivel]];
    }

    public function test_cada_proveedor_genera_su_resultado_limpio_sin_crear_proyectos_ni_fichas(): void
    {
        StructuredAnonymousAgent::fake(fn ($prompt, $adjuntos, $proveedor) => $this->respuestaProyecto('Responde '.$proveedor->name()));
        $caso = IaCaso::factory()->create();

        $this->artisan('ia:bateria', ['--caso' => $caso->id])->assertSuccessful();

        $ejecuciones = IaEjecucion::orderBy('id')->get();
        $this->assertSame(['anthropic', 'openai', 'gemini'], $ejecuciones->pluck('proveedor')->all());
        foreach ($ejecuciones as $e) {
            $this->assertSame('completada', $e->estado);
            $this->assertSame('Responde '.$e->proveedor, $e->resultado['titulo']);
            $this->assertSame(['LCL.C2.4.1'], $e->resultado['curriculo']['criterios']);
            $this->assertSame($e->proveedor, $e->generacion->proveedor);
            $this->assertSame('bateria', $e->generacion->tarea);
            $this->assertSame(1, $e->generacion->ids_descartados);
        }
        $this->assertSame(0, Proyecto::count());
        $this->assertSame(0, Ficha::count());
    }

    public function test_el_administrador_lanza_un_caso_de_ficha_desde_la_pagina(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaFicha()]);
        $caso = IaCaso::factory()->ficha()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.pruebas.index')
            ->set('proveedores', ['anthropic'])
            ->call('lanzar', $caso->id)
            ->assertHasNoErrors();

        $ejecucion = IaEjecucion::sole();
        $this->assertSame('completada', $ejecucion->estado);
        $this->assertSame('El koala', $ejecucion->resultado['titulo']);
        $this->assertSame(['CN.C2.5.1'], $ejecucion->resultado['criterios']);
        $this->assertSame(['refuerzo', 'estandar', 'ampliacion'], array_keys($ejecucion->resultado['contenido']));
        $this->assertSame(0, Ficha::count());
    }

    public function test_si_un_proveedor_falla_su_ejecucion_queda_fallida_y_las_demas_siguen(): void
    {
        StructuredAnonymousAgent::fake(fn ($prompt, $adjuntos, $proveedor) => $proveedor->name() === 'gemini'
            ? throw new RuntimeException('Sin clave de Gemini')
            : $this->respuestaProyecto());
        $caso = IaCaso::factory()->create();

        $this->artisan('ia:bateria', ['--caso' => $caso->id, '--proveedores' => 'anthropic,gemini'])->assertSuccessful();

        $this->assertSame('completada', IaEjecucion::where('proveedor', 'anthropic')->sole()->estado);
        $gemini = IaEjecucion::where('proveedor', 'gemini')->sole();
        $this->assertSame('fallida', $gemini->estado);
        $this->assertSame('Sin clave de Gemini', $gemini->error);
    }

    public function test_un_proveedor_desconocido_no_lanza_nada(): void
    {
        StructuredAnonymousAgent::fake()->preventStrayPrompts();
        IaCaso::factory()->create();

        $this->artisan('ia:bateria', ['--proveedores' => 'anthropic,inventado'])->assertFailed();

        $this->assertSame(0, IaEjecucion::count());
    }

    public function test_entran_el_administrador_y_quien_valora_pero_no_otra_docente(): void
    {
        $caso = IaCaso::factory()->create();
        $docente = User::factory()->create();
        $valoradora = User::factory()->valoradora()->create();

        $this->actingAs($docente)->get(route('admin.pruebas'))->assertForbidden();
        $this->actingAs($docente)->get(route('admin.pruebas.ver', $caso))->assertForbidden();
        $this->actingAs($docente)->get(route('dashboard'))->assertOk()->assertDontSee('Valorar pruebas');

        $this->actingAs($valoradora)->get(route('admin.pruebas'))->assertOk()->assertSee($caso->nombre);
        $this->actingAs($valoradora)->get(route('admin.pruebas.ver', $caso))->assertOk();
        $this->actingAs($valoradora)->get(route('dashboard'))->assertOk()->assertSee('Valorar pruebas');
        $this->actingAs($valoradora)->get(route('admin.ia'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.pruebas'))->assertOk();
    }

    public function test_quien_valora_no_puede_lanzar_pruebas(): void
    {
        $caso = IaCaso::factory()->create();

        Volt::actingAs(User::factory()->valoradora()->create())->test('pages.admin.pruebas.index')
            ->call('lanzar', $caso->id)
            ->assertForbidden();

        $this->assertSame(0, IaEjecucion::count());
    }

    public function test_cada_persona_guarda_su_valoracion_de_1_a_5(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaProyecto()]);
        $caso = IaCaso::factory()->create();
        $this->artisan('ia:bateria', ['--caso' => $caso->id, '--proveedores' => 'anthropic'])->assertSuccessful();
        $ejecucion = IaEjecucion::sole();
        $valoradora = User::factory()->valoradora()->create();
        $admin = User::factory()->admin()->create();

        Volt::actingAs($valoradora)->test('pages.admin.pruebas.ver', ['caso' => $caso])
            ->set("puntuaciones.{$ejecucion->id}", 7)
            ->call('valorar', $ejecucion->id)
            ->assertHasErrors("puntuaciones.{$ejecucion->id}")
            ->set("puntuaciones.{$ejecucion->id}", 4)
            ->set("notas.{$ejecucion->id}", 'Muy concreto, lo usaría.')
            ->call('valorar', $ejecucion->id)
            ->assertHasNoErrors();
        Volt::actingAs($admin)->test('pages.admin.pruebas.ver', ['caso' => $caso])
            ->set("puntuaciones.{$ejecucion->id}", 2)
            ->call('valorar', $ejecucion->id);

        $this->assertSame(4, $ejecucion->valoracionDe($valoradora)->puntuacion);
        $this->assertSame('Muy concreto, lo usaría.', $ejecucion->valoracionDe($valoradora)->nota);
        $this->assertSame(2, $ejecucion->valoracionDe($admin)->puntuacion);
    }

    public function test_a_ciegas_no_se_ve_el_proveedor_hasta_valorar_todas_las_opciones(): void
    {
        StructuredAnonymousAgent::fake(fn () => $this->respuestaProyecto());
        $caso = IaCaso::factory()->create();
        $this->artisan('ia:bateria', ['--caso' => $caso->id, '--proveedores' => 'anthropic,openai'])->assertSuccessful();
        [$primera, $segunda] = IaEjecucion::orderBy('id')->get()->all();

        $pagina = Volt::actingAs(User::factory()->valoradora()->create())->test('pages.admin.pruebas.ver', ['caso' => $caso])
            ->assertSee('Exploradores del planeta')
            ->assertDontSee('anthropic')
            ->assertDontSee('openai')
            ->set("puntuaciones.{$primera->id}", 5)
            ->call('valorar', $primera->id)
            ->assertDontSee('anthropic');

        $pagina->set("puntuaciones.{$segunda->id}", 3)
            ->call('valorar', $segunda->id)
            ->assertSee('anthropic')
            ->assertSee('openai');
    }

    public function test_usuarios_valorar_da_y_quita_el_permiso(): void
    {
        $usuaria = User::factory()->create();

        $this->artisan('usuarios:valorar', ['email' => $usuaria->email])->assertSuccessful();
        $this->assertTrue($usuaria->fresh()->puede_valorar);

        $this->artisan('usuarios:valorar', ['email' => $usuaria->email, '--quitar' => true])->assertSuccessful();
        $this->assertFalse($usuaria->fresh()->puede_valorar);
    }

    public function test_las_pruebas_salen_aparte_en_el_uso_de_la_ia(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaProyecto()]);
        $this->artisan('ia:bateria', ['--caso' => IaCaso::factory()->create()->id, '--proveedores' => 'anthropic'])->assertSuccessful();

        $this->assertSame(['bateria'], Generacion::pluck('tarea')->all());
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.ia.uso'))->assertOk()->assertSee('Batería de pruebas');
    }
}

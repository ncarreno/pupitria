<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Clase;
use App\Models\Generacion;
use App\Models\Proyecto;
use App\Models\User;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\StructuredAnonymousAgent;
use Livewire\Volt\Volt;
use RuntimeException;
use Tests\TestCase;

class ProyectosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
        $this->seed(IaConfiguracionSeeder::class);
    }

    /**
     * Respuesta simulada de la IA con dos ids inventados (uno de otro ciclo, otro inexistente).
     *
     * @return array<string, mixed>
     */
    private function respuestaIA(string $titulo = 'Exploradores del planeta'): array
    {
        return [
            'titulo' => $titulo,
            'lema' => 'Una expedición por los continentes',
            'temporalizacion' => '6 semanas',
            'narracion' => ['secuencia' => 'Llega un baúl de expedición.', 'vinculacion' => 'Les encantan los animales.'],
            'curriculo' => [
                'criterios' => ['LCL.C2.4.1', 'CN.C2.2.2', 'LCL.C1.4.1', 'CN.C9.9.9'],
                'saberes' => ['CN.C2.A.2.4', 'MAT.C2.A.1.1'],
            ],
            'ods' => ['desafio' => 'Cuidar los ecosistemas.', 'lista' => [4, 15, 99]],
            'producto_final' => 'Museo de los continentes.',
            'tareas' => [
                ['titulo' => 'Abrimos el baúl', 'descripcion' => 'Leemos el cuaderno de viaje.', 'areas' => ['LCL', 'MAT'], 'criterios' => ['LCL.C2.4.1', 'XX.1']],
            ],
            'agrupamientos' => 'Cinco expediciones.',
            'espacios' => 'Aula y biblioteca.',
            'metodologias' => [['nombre' => 'ABP', 'descripcion' => 'Todo conduce al museo.']],
            'recursos' => 'Globo terráqueo.',
            'tic' => 'Mapas digitales.',
            'evaluacion' => [['instrumento' => 'Rúbrica', 'que' => 'Diario de viaje.']],
            'difusion' => ['Visita de 1.º'],
            'itinerario' => [],
        ];
    }

    /**
     * Contenido ya validado, como el que guarda la aplicación.
     *
     * @return array<string, mixed>
     */
    private function contenidoValido(string $titulo = 'Exploradores del planeta'): array
    {
        $contenido = $this->respuestaIA($titulo);
        $contenido['curriculo'] = ['criterios' => ['LCL.C2.4.1', 'CN.C2.2.2'], 'saberes' => ['CN.C2.A.2.4']];

        return $contenido;
    }

    private function claseDe3(): Clase
    {
        $clase = Clase::factory()->create(['curso' => 3, 'alumnos' => 17]);
        $clase->areas()->sync(Area::whereIn('codigo', ['LCL', 'CN', 'CS', 'MAT'])->pluck('id'));

        return $clase;
    }

    public function test_crear_un_proyecto_genera_el_documento_y_descarta_lo_inventado(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.proyectos.nuevo')
            ->set('claseId', $clase->id)
            ->set('idea', 'Un viaje por los continentes y sus animales')
            ->set('areas', ['LCL', 'CN'])
            ->call('crear')
            ->assertHasNoErrors();

        $proyecto = Proyecto::sole();
        $this->assertSame('listo', $proyecto->estado);
        $this->assertSame('Exploradores del planeta', $proyecto->titulo);
        $this->assertSame(['LCL.C2.4.1', 'CN.C2.2.2'], $proyecto->contenido['curriculo']['criterios']);
        $this->assertSame(['CN.C2.A.2.4'], $proyecto->contenido['curriculo']['saberes']);
        $this->assertSame(['LCL'], $proyecto->contenido['tareas'][0]['areas']);
        $this->assertSame(['LCL.C2.4.1'], $proyecto->contenido['tareas'][0]['criterios']);
        $this->assertSame([4, 15], $proyecto->contenido['ods']['lista']);
        $this->assertSame(4, $proyecto->ids_descartados);
        $this->assertEqualsCanonicalizing(['LCL.C2.4.1', 'CN.C2.2.2'], $proyecto->criterios->pluck('id')->all());
        $this->assertSame(1, $proyecto->versiones()->count());
        $this->assertSame(4, Generacion::sole()->ids_descartados);
    }

    public function test_no_se_puede_crear_un_proyecto_con_la_clase_de_otra_docente(): void
    {
        $ajena = $this->claseDe3();

        Volt::actingAs(User::factory()->create())->test('pages.proyectos.nuevo')
            ->set('claseId', $ajena->id)
            ->set('idea', 'Un viaje por los continentes y sus animales')
            ->set('areas', ['LCL'])
            ->call('crear')
            ->assertForbidden();

        $this->assertSame(0, Proyecto::count());
    }

    public function test_solo_la_autora_puede_ver_el_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('proyectos.ver', $proyecto))->assertForbidden();
        $this->actingAs($proyecto->user)->get(route('proyectos.ver', $proyecto))->assertOk();
    }

    public function test_ajustar_crea_una_version_nueva_con_la_instruccion(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA(), $this->respuestaIA('Exploradores de Asturias')]);
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.proyectos.nuevo')
            ->set('claseId', $clase->id)->set('idea', 'Un viaje por los continentes')->set('areas', ['LCL', 'CN'])
            ->call('crear');
        $proyecto = Proyecto::sole();

        Volt::actingAs($clase->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('ajustar', 'Conéctalo más con Asturias');

        $proyecto->refresh();
        $this->assertSame('Exploradores de Asturias', $proyecto->titulo);
        $this->assertSame(['Conéctalo más con Asturias', null], $proyecto->versiones->pluck('instruccion')->all());
    }

    public function test_si_falla_la_primera_generacion_queda_en_error(): void
    {
        StructuredAnonymousAgent::fake(fn () => throw new RuntimeException('Proveedor caído'));
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.proyectos.nuevo')
            ->set('claseId', $clase->id)->set('idea', 'Un viaje por los continentes')->set('areas', ['LCL'])
            ->call('crear');

        $proyecto = Proyecto::sole();
        $this->assertSame('error', $proyecto->estado);
        $this->assertNotNull($proyecto->error);
        $this->assertSame('fallida', Generacion::sole()->estado);
    }

    public function test_edicion_a_mano_solo_en_campos_permitidos(): void
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => $this->contenidoValido()]);

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('actualizar', 'tareas.0.titulo', 'Abrimos el baúl de Nalón')
            ->call('actualizar', 'curriculo.criterios.0', 'INVENTADO.1');

        $contenido = $proyecto->fresh()->contenido;
        $this->assertSame('Abrimos el baúl de Nalón', $contenido['tareas'][0]['titulo']);
        $this->assertSame('LCL.C2.4.1', $contenido['curriculo']['criterios'][0]);
    }

    public function test_otra_docente_no_puede_editar_ni_borrar(): void
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => $this->contenidoValido()]);
        $intrusa = User::factory()->create();

        Volt::actingAs($intrusa)->test('pages.proyectos.ver', ['proyecto' => $proyecto])->assertForbidden();

        $this->assertModelExists($proyecto);
    }

    public function test_el_listado_solo_muestra_mis_proyectos(): void
    {
        $mio = Proyecto::factory()->create(['titulo' => 'Mi proyecto del agua']);
        Proyecto::factory()->create(['titulo' => 'Proyecto de otra docente']);

        $this->actingAs($mio->user)->get(route('proyectos'))
            ->assertOk()
            ->assertSee('Mi proyecto del agua')
            ->assertDontSee('Proyecto de otra docente');
    }

    public function test_el_listado_muestra_la_idea_de_un_proyecto_que_no_se_pudo_generar(): void
    {
        $proyecto = Proyecto::factory()->create(['titulo' => '', 'idea' => 'El agua en Asturias, de los ríos al mar', 'estado' => 'error']);

        $this->actingAs($proyecto->user)->get(route('proyectos'))
            ->assertOk()
            ->assertSee('El agua en Asturias, de los ríos al mar')
            ->assertDontSee('Generando…');
    }

    public function test_restaurar_una_version_anterior(): void
    {
        $proyecto = Proyecto::factory()->create();
        $v1 = $proyecto->guardarVersion($this->contenidoValido('Versión uno'));
        $proyecto->guardarVersion($this->contenidoValido('Versión dos'), 'Cambio');

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto->fresh()])
            ->call('restaurar', $v1->id);

        $this->assertSame('Versión uno', $proyecto->fresh()->titulo);
        $this->assertSame(3, $proyecto->versiones()->count());
    }

    public function test_no_se_restaura_ni_se_edita_mientras_se_genera_un_ajuste(): void
    {
        $proyecto = Proyecto::factory()->create();
        $v1 = $proyecto->guardarVersion($this->contenidoValido('Versión uno'));
        $proyecto->guardarVersion($this->contenidoValido('Versión dos'), 'Cambio');
        $proyecto->forceFill(['estado' => 'generando'])->save();

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto->fresh()])
            ->call('restaurar', $v1->id)
            ->call('actualizar', 'lema', 'Editado durante el ajuste');

        $proyecto->refresh();
        $this->assertSame('generando', $proyecto->estado);
        $this->assertSame('Versión dos', $proyecto->titulo);
        $this->assertSame('Una expedición por los continentes', $proyecto->contenido['lema']);
        $this->assertSame(2, $proyecto->versiones()->count());
    }

    public function test_el_titulo_editado_a_mano_se_corta_a_255_caracteres(): void
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => $this->contenidoValido()]);

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('actualizar', 'titulo', str_repeat('a', 300));

        $proyecto->refresh();
        $this->assertSame(str_repeat('a', 255), $proyecto->titulo);
        $this->assertSame(str_repeat('a', 255), $proyecto->contenido['titulo']);
    }
}

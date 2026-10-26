<?php

namespace Tests\Feature;

use App\Jobs\GenerarFicha;
use App\Models\Area;
use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Generacion;
use App\Models\Proyecto;
use App\Models\User;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Laravel\Ai\StructuredAnonymousAgent;
use Livewire\Volt\Volt;
use RuntimeException;
use Tests\TestCase;

class FichasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
        $this->seed(IaConfiguracionSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function bloque(string $tipo, array $campos = []): array
    {
        return array_merge([
            'tipo' => $tipo, 'titulo' => '', 'enunciado' => '', 'texto' => '', 'preguntas' => [], 'lineas' => 2,
            'items' => [], 'frases' => [], 'banco' => [], 'columnas' => [], 'filas' => [], 'izquierda' => [], 'derecha' => [],
        ], $campos);
    }

    /**
     * @return array<string, mixed>
     */
    private function respuestaIA(): array
    {
        $nivel = fn (string $lectura) => [
            'consigna' => 'Lee el texto y responde.',
            'bloques' => [
                $this->bloque('lectura', ['titulo' => '¿Quién es el koala?', 'texto' => $lectura]),
                $this->bloque('verdadero_falso', ['enunciado' => 'Escribe V o F.', 'frases' => ['El koala bebe mucha agua.']]),
                $this->bloque('inventado', ['texto' => 'Esto no debería aparecer']),
            ],
        ];

        return [
            'titulo' => 'El koala y su bosque',
            'criterios' => ['CN.C2.5.1', 'CN.C9.9.9', 'LCL.C2.4.1'],
            'ilustracion' => 'Un koala dormido en un eucalipto',
            'niveles' => [
                'refuerzo' => $nivel('El koala vive en Australia.'),
                'estandar' => $nivel('El koala vive en los bosques de eucaliptos del este de Australia.'),
                'ampliacion' => $nivel('El koala depende de los bosques de eucaliptos.'),
            ],
        ];
    }

    private function claseDe3(): Clase
    {
        $clase = Clase::factory()->create(['curso' => 3]);
        $clase->areas()->sync(Area::whereIn('codigo', ['CN', 'LCL'])->pluck('id'));

        return $clase;
    }

    public function test_crear_una_ficha_genera_los_tres_niveles_y_limpia_lo_inventado(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)
            ->set('area', 'CN')
            ->set('tema', 'Animales de Oceanía y cómo se adaptan')
            ->call('crear')
            ->assertHasNoErrors();

        $ficha = Ficha::sole();
        $this->assertSame('listo', $ficha->estado);
        $this->assertSame('El koala y su bosque', $ficha->titulo);
        $this->assertSame(['refuerzo', 'estandar', 'ampliacion'], array_keys($ficha->contenido));
        $this->assertSame(['lectura', 'verdadero_falso'], array_column($ficha->contenido['estandar']['bloques'], 'tipo'));
        $this->assertSame(['CN.C2.5.1'], $ficha->criterios->pluck('id')->all());
        $this->assertSame(2, $ficha->ids_descartados);
        $this->assertNull($ficha->imagen);
    }

    public function test_con_ilustracion_se_genera_la_imagen_con_openai(): void
    {
        Storage::fake('public');
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        Image::fake();
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->set('ilustracion', 'openai')
            ->call('crear');

        $ficha = Ficha::sole();
        $this->assertNotNull($ficha->imagen);
        Storage::disk('public')->assertExists($ficha->imagen);
        Image::assertGenerated(fn ($prompt) => str_contains($prompt->prompt, 'Un koala dormido en un eucalipto'));
        $this->assertSame('openai', Generacion::where('tarea', 'imagen')->sole()->proveedor);
    }

    public function test_si_falla_la_ilustracion_la_ficha_queda_lista_sin_imagen(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        Image::fake(fn () => throw new RuntimeException('Sin saldo'));
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->set('ilustracion', 'openai')
            ->call('crear');

        $ficha = Ficha::sole();
        $this->assertSame('listo', $ficha->estado);
        $this->assertNull($ficha->imagen);
        $this->assertNotNull($ficha->error);
        $this->assertStringContainsString('Un koala dormido en un eucalipto', $ficha->prompt_imagen);
    }

    public function test_con_prompt_no_se_llama_a_openai_y_se_guarda_el_prompt(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        Image::fake();
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->set('ilustracion', 'prompt')
            ->call('crear');

        $ficha = Ficha::sole();
        $this->assertSame('listo', $ficha->estado);
        $this->assertTrue($ficha->ilustracionPorPrompt());
        $this->assertNull($ficha->imagen);
        $this->assertNull($ficha->error);
        $this->assertStringContainsString('Un koala dormido en un eucalipto', $ficha->prompt_imagen);
        $this->assertStringContainsString('sin ningún texto', $ficha->prompt_imagen);
        Image::assertNothingGenerated();
        $this->assertSame(0, Generacion::where('tarea', 'imagen')->count());

        $this->actingAs($clase->user)->get(route('fichas.ver', $ficha))
            ->assertOk()->assertSee('Copiar el prompt')->assertSee('Un koala dormido en un eucalipto');
    }

    public function test_sin_ilustracion_no_hay_prompt(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->call('crear');

        $ficha = Ficha::sole();
        $this->assertFalse($ficha->con_imagen);
        $this->assertNull($ficha->prompt_imagen);
    }

    public function test_la_autora_sube_y_quita_la_imagen_hecha_fuera(): void
    {
        Storage::fake('public');
        $ficha = Ficha::factory()->create([
            'estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles'],
            'con_imagen' => true, 'imagen_por' => 'prompt', 'prompt_imagen' => 'Crea una imagen de un koala.',
        ]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->set('nuevaImagen', UploadedFile::fake()->image('koala.png', 1200, 800))
            ->assertHasNoErrors();

        $ruta = $ficha->refresh()->imagen;
        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->set('nuevaImagen', UploadedFile::fake()->create('notas.pdf', 10, 'application/pdf'))
            ->assertHasErrors('nuevaImagen');
        $this->assertSame($ruta, $ficha->refresh()->imagen);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])->call('quitarImagen');
        $this->assertNull($ficha->refresh()->imagen);
        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_otra_docente_no_puede_subir_imagen(): void
    {
        Storage::fake('public');
        $ficha = Ficha::factory()->create([
            'estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles'],
            'con_imagen' => true, 'imagen_por' => 'prompt', 'prompt_imagen' => 'x',
        ]);

        Volt::actingAs(User::factory()->create())->test('pages.fichas.ver', ['ficha' => $ficha])
            ->assertForbidden();
        $this->assertNull($ficha->refresh()->imagen);
    }

    public function test_si_falla_el_texto_la_ficha_queda_en_error(): void
    {
        StructuredAnonymousAgent::fake(fn () => throw new RuntimeException('Proveedor caído'));
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->call('crear');

        $this->assertSame('error', Ficha::sole()->estado);
    }

    public function test_desde_una_tarea_del_proyecto_se_rellena_el_tema_y_el_area(): void
    {
        $clase = $this->claseDe3();
        $proyecto = Proyecto::factory()->for($clase)->create([
            'user_id' => $clase->user_id,
            'estado' => 'listo',
            'contenido' => ['tareas' => [['titulo' => 'Fichas de animales', 'descripcion' => 'Cómo se adaptan a su ecosistema.', 'areas' => ['CN', 'LCL'], 'criterios' => []]]],
        ]);

        Volt::actingAs($clase->user)->test('pages.fichas.nueva', ['proyecto' => $proyecto->id, 'tarea' => 0])
            ->assertSet('area', 'CN')
            ->assertSet('tema', 'Fichas de animales: Cómo se adaptan a su ecosistema.');
    }

    public function test_el_enlace_desde_el_proyecto_abre_la_ficha_rellenada(): void
    {
        $clase = $this->claseDe3();
        $proyecto = Proyecto::factory()->for($clase)->create([
            'user_id' => $clase->user_id,
            'estado' => 'listo',
            'contenido' => ['tareas' => [['titulo' => 'Fichas de animales', 'descripcion' => 'Cómo se adaptan.', 'areas' => ['CN'], 'criterios' => []]]],
        ]);

        $this->actingAs($clase->user)
            ->get(route('fichas.nueva', ['proyecto' => $proyecto->id, 'tarea' => 0]))
            ->assertOk()
            ->assertSeeInOrder(['Para el proyecto', $proyecto->titulo, 'tarea 1']);
    }

    public function test_no_se_usa_el_proyecto_de_otra_docente(): void
    {
        $ajeno = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => ['tareas' => [['titulo' => 'Secreta', 'descripcion' => 'x', 'areas' => ['CN'], 'criterios' => []]]]]);
        $docente = $this->claseDe3()->user;

        Volt::actingAs($docente)->test('pages.fichas.nueva', ['proyecto' => $ajeno->id, 'tarea' => 0])
            ->assertSet('tema', '')
            ->assertDontSee('Secreta');
    }

    public function test_solo_la_autora_ve_y_edita_la_ficha(): void
    {
        $ficha = Ficha::factory()->create(['estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles']]);

        $this->actingAs(User::factory()->create())->get(route('fichas.ver', $ficha))->assertForbidden();
        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))->assertOk()->assertSee('¿Quién es el koala?');
    }

    public function test_edicion_a_mano_solo_en_textos_permitidos(): void
    {
        $ficha = Ficha::factory()->create(['estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles']]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('actualizar', 'estandar.bloques.0.texto', 'El koala vive en Australia y come eucalipto.')
            ->call('actualizar', 'estandar.bloques.0.tipo', 'dibujo')
            ->call('actualizar', 'titulo', 'El koala');

        $ficha->refresh();
        $this->assertSame('El koala vive en Australia y come eucalipto.', $ficha->contenido['estandar']['bloques'][0]['texto']);
        $this->assertSame('lectura', $ficha->contenido['estandar']['bloques'][0]['tipo']);
        $this->assertSame('El koala', $ficha->titulo);
    }

    public function test_el_titulo_editado_a_mano_se_corta_a_255_caracteres(): void
    {
        $ficha = Ficha::factory()->create(['estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles']]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('actualizar', 'titulo', str_repeat('a', 300));

        $this->assertSame(str_repeat('a', 255), $ficha->refresh()->titulo);
    }

    public function test_mientras_se_regenera_no_se_guardan_ediciones_a_mano(): void
    {
        $ficha = Ficha::factory()->create(['estado' => 'generando', 'contenido' => $this->respuestaIA()['niveles']]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('actualizar', 'titulo', 'Editado durante la generación');

        $this->assertSame('El koala', $ficha->refresh()->titulo);
    }

    public function test_si_la_cola_corta_el_job_la_ficha_no_se_queda_generando(): void
    {
        $ficha = Ficha::factory()->create(['estado' => 'generando']);

        (new GenerarFicha($ficha))->failed(new RuntimeException('Timeout del worker'));

        $ficha->refresh();
        $this->assertSame('error', $ficha->estado);
        $this->assertSame('No se ha podido generar la ficha. Vuelve a intentarlo en unos minutos.', $ficha->error);
    }

    public function test_si_la_ia_no_propone_ilustracion_el_prompt_ilustra_el_titulo(): void
    {
        StructuredAnonymousAgent::fake([array_merge($this->respuestaIA(), ['ilustracion' => ''])]);
        $clase = $this->claseDe3();

        Volt::actingAs($clase->user)->test('pages.fichas.nueva')
            ->set('claseId', $clase->id)->set('area', 'CN')->set('tema', 'El koala y su bosque')
            ->set('ilustracion', 'prompt')
            ->call('crear');

        $this->assertStringContainsString('«El koala y su bosque»', Ficha::sole()->prompt_imagen);
    }

    public function test_al_regenerar_con_openai_se_borra_la_imagen_anterior(): void
    {
        Storage::fake('public');
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        Image::fake();
        Storage::disk('public')->put('imagenes/vieja.png', 'imagen');
        $ficha = Ficha::factory()->create([
            'estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles'],
            'con_imagen' => true, 'imagen_por' => 'openai', 'imagen' => 'imagenes/vieja.png',
        ]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])->call('regenerar');

        $ficha->refresh();
        $this->assertNotSame('imagenes/vieja.png', $ficha->imagen);
        Storage::disk('public')->assertExists($ficha->imagen);
        Storage::disk('public')->assertMissing('imagenes/vieja.png');
    }

    public function test_si_falla_la_ilustracion_al_regenerar_se_ofrece_el_prompt_y_se_conserva_la_imagen(): void
    {
        Storage::fake('public');
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        Image::fake(fn () => throw new RuntimeException('Sin saldo'));
        Storage::disk('public')->put('imagenes/vieja.png', 'imagen');
        $ficha = Ficha::factory()->create([
            'estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles'],
            'con_imagen' => true, 'imagen_por' => 'openai', 'imagen' => 'imagenes/vieja.png',
        ]);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])->call('regenerar');

        $this->assertSame('imagenes/vieja.png', $ficha->refresh()->imagen);
        Storage::disk('public')->assertExists('imagenes/vieja.png');
        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSee('Copiar el prompt');
    }

    public function test_al_borrar_la_ficha_se_borra_su_imagen(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('imagenes/koala.png', 'imagen');
        $ficha = Ficha::factory()->create(['estado' => 'listo', 'contenido' => $this->respuestaIA()['niveles'], 'imagen' => 'imagenes/koala.png']);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])->call('borrar');

        $this->assertModelMissing($ficha);
        Storage::disk('public')->assertMissing('imagenes/koala.png');
    }

    public function test_una_tarea_que_no_existe_no_se_guarda_en_la_ficha(): void
    {
        StructuredAnonymousAgent::fake([$this->respuestaIA()]);
        $clase = $this->claseDe3();
        $proyecto = Proyecto::factory()->for($clase)->create([
            'user_id' => $clase->user_id,
            'estado' => 'listo',
            'contenido' => ['tareas' => [['titulo' => 'Fichas de animales', 'descripcion' => 'Cómo se adaptan.', 'areas' => ['CN'], 'criterios' => []]]],
        ]);

        Volt::actingAs($clase->user)->test('pages.fichas.nueva', ['proyecto' => $proyecto->id, 'tarea' => -1])
            ->assertSet('tarea', null)
            ->set('tema', 'El koala y su bosque')
            ->call('crear')
            ->assertHasNoErrors();

        $ficha = Ficha::sole();
        $this->assertSame($proyecto->id, $ficha->proyecto_id);
        $this->assertNull($ficha->tarea_indice);
    }

    public function test_el_listado_muestra_el_tema_de_una_ficha_que_no_se_pudo_generar(): void
    {
        $ficha = Ficha::factory()->create(['titulo' => '', 'tema' => 'Animales de Oceanía', 'estado' => 'error']);

        $this->actingAs($ficha->user)->get(route('fichas'))
            ->assertOk()
            ->assertSee('Animales de Oceanía')
            ->assertDontSee('Generando…');
    }

    public function test_un_bloque_con_titulo_y_enunciado_muestra_los_dos(): void
    {
        $bloques = [$this->bloque('preguntas', ['titulo' => 'Piensa', 'enunciado' => 'Responde con frases completas.', 'preguntas' => ['¿Qué come el koala?']])];
        $ficha = Ficha::factory()->create(['estado' => 'listo', 'contenido' => [
            'refuerzo' => ['consigna' => '', 'bloques' => []],
            'estandar' => ['consigna' => 'Lee y responde.', 'bloques' => $bloques],
            'ampliacion' => ['consigna' => '', 'bloques' => []],
        ]]);

        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSeeInOrder(['Piensa', 'Responde con frases completas.', '¿Qué come el koala?']);
    }
}

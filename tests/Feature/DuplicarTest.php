<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DuplicarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
    }

    private function proyectoListo(): Proyecto
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'estilo' => 'cartulina']);
        $proyecto->guardarVersion([
            'titulo' => 'Exploradores del planeta',
            'curriculo' => ['criterios' => ['CN.C2.5.1'], 'saberes' => ['CN.C2.A.2.1']],
        ]);

        return $proyecto->refresh();
    }

    private function fichaLista(): Ficha
    {
        $nivel = ['consigna' => 'Lee y responde.', 'bloques' => []];
        $ficha = Ficha::factory()->create([
            'estado' => 'listo',
            'titulo' => 'El koala',
            'estilo' => 'esencial',
            'contenido' => ['refuerzo' => $nivel, 'estandar' => $nivel, 'ampliacion' => $nivel],
        ]);
        $ficha->criterios()->attach('CN.C2.5.1');

        return $ficha;
    }

    public function test_duplicar_un_proyecto_en_otra_clase_del_mismo_curso(): void
    {
        $proyecto = $this->proyectoListo();
        $otraClase = Clase::factory()->for($proyecto->user)->create(['curso' => 3]);

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('abrirDuplicar')
            ->assertSet('claseDestino', (string) $proyecto->clase_id)
            ->set('claseDestino', (string) $otraClase->id)
            ->call('duplicar')
            ->assertRedirect(route('proyectos.ver', Proyecto::latest('id')->first()));

        $copia = Proyecto::latest('id')->first();
        $this->assertNotSame($proyecto->id, $copia->id);
        $this->assertSame($otraClase->id, $copia->clase_id);
        $this->assertSame($proyecto->user_id, $copia->user_id);
        $this->assertSame('Exploradores del planeta (copia)', $copia->titulo);
        $this->assertSame('listo', $copia->estado);
        $this->assertSame('cartulina', $copia->estilo);
        $this->assertSame(['CN.C2.5.1'], $copia->criterios()->pluck('criterios.id')->all());
        $this->assertSame(['CN.C2.A.2.1'], $copia->saberes()->pluck('saberes.id')->all());
        $this->assertSame(1, $copia->versiones()->count());
        $this->assertSame('Copia de «Exploradores del planeta»', $copia->versiones()->first()->instruccion);
        $this->assertSame('Exploradores del planeta', $proyecto->refresh()->titulo);
    }

    public function test_duplicar_una_ficha_copia_la_imagen_en_un_archivo_nuevo(): void
    {
        Storage::fake('public');
        $ficha = $this->fichaLista();
        Storage::disk('public')->put('imagenes/'.$ficha->user_id.'/koala.png', 'png');
        $ficha->forceFill(['imagen' => 'imagenes/'.$ficha->user_id.'/koala.png'])->save();

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('abrirDuplicar')
            ->call('duplicar');

        $copia = Ficha::latest('id')->first();
        $this->assertSame('El koala (copia)', $copia->titulo);
        $this->assertSame('esencial', $copia->estilo);
        $this->assertSame($ficha->contenido, $copia->contenido);
        $this->assertSame(['CN.C2.5.1'], $copia->criterios()->pluck('criterios.id')->all());
        $this->assertNotSame($ficha->imagen, $copia->imagen);
        Storage::disk('public')->assertExists($copia->imagen);

        $copia->delete();

        Storage::disk('public')->assertExists($ficha->imagen);
    }

    public function test_no_se_duplica_en_una_clase_de_otro_curso_ni_ajena(): void
    {
        $proyecto = $this->proyectoListo();
        $deOtroCurso = Clase::factory()->for($proyecto->user)->create(['curso' => 5]);
        $ajena = Clase::factory()->create(['curso' => 3]);
        $pagina = Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])->call('abrirDuplicar');

        $pagina->set('claseDestino', (string) $deOtroCurso->id)->call('duplicar')->assertHasErrors('claseDestino');
        $pagina->set('claseDestino', (string) $ajena->id)->call('duplicar')->assertHasErrors('claseDestino');

        $this->assertSame(1, Proyecto::count());
    }

    public function test_otra_docente_no_puede_duplicar(): void
    {
        $ficha = $this->fichaLista();
        $pagina = Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])->call('abrirDuplicar');
        $this->actingAs(User::factory()->create());

        $pagina->call('duplicar')->assertForbidden();

        $this->assertSame(1, Ficha::count());
    }
}

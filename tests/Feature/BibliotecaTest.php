<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BibliotecaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function proyectoDe(User $user, array $atributos): Proyecto
    {
        $clase = Clase::factory()->for($user)->create(['curso' => $atributos['curso'] ?? 3]);

        return Proyecto::factory()->create($atributos + ['clase_id' => $clase->id, 'estado' => 'listo']);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function fichaDe(User $user, array $atributos): Ficha
    {
        $clase = Clase::factory()->for($user)->create(['curso' => $atributos['curso'] ?? 3]);

        return Ficha::factory()->create($atributos + ['clase_id' => $clase->id, 'estado' => 'listo']);
    }

    public function test_buscar_proyectos_por_titulo_o_idea(): void
    {
        $user = User::factory()->create();
        $this->proyectoDe($user, ['titulo' => 'Exploradores del planeta', 'idea' => 'Un viaje por los continentes']);
        $this->proyectoDe($user, ['titulo' => 'Artes escénicas', 'idea' => 'Una obra de teatro para el centro']);

        Volt::actingAs($user)->test('pages.proyectos.index')
            ->set('buscar', 'teatro')
            ->assertSee('Artes escénicas')
            ->assertDontSee('Exploradores del planeta')
            ->set('buscar', 'exploradores')
            ->assertSee('Exploradores del planeta')
            ->assertDontSee('Artes escénicas');
    }

    public function test_filtrar_proyectos_por_area_curso_y_criterio(): void
    {
        $user = User::factory()->create();
        $ciencias = $this->proyectoDe($user, ['titulo' => 'El agua del patio', 'curso' => 3, 'areas' => ['CN', 'LCL']]);
        $this->proyectoDe($user, ['titulo' => 'La tienda de la clase', 'curso' => 5, 'areas' => ['MAT']]);
        $ciencias->criterios()->attach('CN.C2.5.1');

        $pagina = Volt::actingAs($user)->test('pages.proyectos.index');

        $pagina->set('area', 'MAT')->assertSee('La tienda de la clase')->assertDontSee('El agua del patio');
        $pagina->set('area', '')->set('curso', '3')->assertSee('El agua del patio')->assertDontSee('La tienda de la clase');
        $pagina->set('curso', '')->set('criterio', 'CN.C2.5.1')->assertSee('El agua del patio')->assertDontSee('La tienda de la clase');
    }

    public function test_la_busqueda_nunca_muestra_proyectos_de_otra_docente(): void
    {
        $user = User::factory()->create();
        $this->proyectoDe($user, ['titulo' => 'Exploradores del planeta']);
        $ajeno = $this->proyectoDe(User::factory()->create(), ['titulo' => 'Exploradores del océano']);
        $ajeno->criterios()->attach('CN.C2.5.1');

        Volt::actingAs($user)->test('pages.proyectos.index')
            ->set('buscar', 'exploradores')
            ->assertSee('Exploradores del planeta')
            ->assertDontSee('Exploradores del océano')
            ->set('buscar', '')
            ->set('criterio', 'CN.C2.5.1')
            ->assertDontSee('Exploradores del océano')
            ->assertSee('Ningún proyecto con estos filtros');
    }

    public function test_sin_resultados_se_pueden_quitar_los_filtros(): void
    {
        $user = User::factory()->create();
        $this->proyectoDe($user, ['titulo' => 'Exploradores del planeta']);

        Volt::actingAs($user)->test('pages.proyectos.index')
            ->set('buscar', 'dinosaurios')
            ->assertSee('Ningún proyecto con estos filtros')
            ->call('quitarFiltros')
            ->assertSet('buscar', '')
            ->assertSee('Exploradores del planeta');
    }

    public function test_los_filtros_van_en_la_url(): void
    {
        $user = User::factory()->create();
        $this->proyectoDe($user, ['titulo' => 'Exploradores del planeta']);
        $this->proyectoDe($user, ['titulo' => 'Artes escénicas']);

        $this->actingAs($user)->get(route('proyectos', ['buscar' => 'artes']))
            ->assertOk()
            ->assertSee('Artes escénicas')
            ->assertDontSee('Exploradores del planeta');
    }

    public function test_filtrar_fichas_por_tema_proyecto_y_criterio_solo_en_lo_propio(): void
    {
        $user = User::factory()->create();
        $proyecto = $this->proyectoDe($user, ['titulo' => 'Exploradores del planeta']);
        $koala = $this->fichaDe($user, ['titulo' => 'El koala', 'tema' => 'Animales de Oceanía', 'proyecto_id' => $proyecto->id]);
        $this->fichaDe($user, ['titulo' => 'Las fracciones', 'tema' => 'Repartir pizzas', 'area' => 'MAT']);
        $koala->criterios()->attach('CN.C2.5.1');
        $ajena = $this->fichaDe(User::factory()->create(), ['titulo' => 'El canguro', 'tema' => 'Animales de Oceanía']);
        $ajena->criterios()->attach('CN.C2.5.1');

        $pagina = Volt::actingAs($user)->test('pages.fichas.index');

        $pagina->set('buscar', 'oceanía')->assertSee('El koala')->assertDontSee('Las fracciones')->assertDontSee('El canguro');
        $pagina->set('buscar', '')->set('proyecto', (string) $proyecto->id)->assertSee('El koala')->assertDontSee('Las fracciones');
        $pagina->set('proyecto', '')->set('criterio', 'CN.C2.5.1')->assertSee('El koala')->assertDontSee('Las fracciones')->assertDontSee('El canguro');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Ficha;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EstilosDocumentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function fichaLista(array $atributos = []): Ficha
    {
        $nivel = ['consigna' => 'Lee y responde.', 'bloques' => [
            ['tipo' => 'lectura', 'titulo' => 'El koala', 'enunciado' => '', 'texto' => 'El koala vive en Australia.'],
        ]];

        return Ficha::factory()->create($atributos + [
            'estado' => 'listo',
            'contenido' => ['refuerzo' => $nivel, 'estandar' => $nivel, 'ampliacion' => $nivel],
        ]);
    }

    private function preferirEstilo(User $user, string $estilo): void
    {
        $user->forceFill(['preferencias' => ['estilo' => $estilo]])->save();
    }

    public function test_sin_estilo_propio_ni_del_perfil_la_ficha_se_ve_como_cuaderno(): void
    {
        $ficha = $this->fichaLista();

        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSee('hoja-ficha estandar t-cuaderno', false);
    }

    public function test_sin_estilo_propio_la_ficha_usa_el_del_perfil(): void
    {
        $ficha = $this->fichaLista();
        $this->preferirEstilo($ficha->user, 'cartulina');

        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSee('hoja-ficha estandar t-cartulina', false)
            ->assertSee('family=baloo-2', false);
    }

    public function test_el_estilo_de_la_ficha_manda_sobre_el_del_perfil(): void
    {
        $ficha = $this->fichaLista(['estilo' => 'esencial']);
        $this->preferirEstilo($ficha->user, 'cartulina');

        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSee('hoja-ficha estandar t-esencial', false);
    }

    public function test_un_estilo_guardado_que_ya_no_existe_se_ve_con_el_del_perfil(): void
    {
        $ficha = $this->fichaLista(['estilo' => 'atlas']);
        $this->preferirEstilo($ficha->user, 'caligrafia');

        $this->actingAs($ficha->user)->get(route('fichas.ver', $ficha))
            ->assertOk()
            ->assertSee('hoja-ficha estandar t-caligrafia', false);
    }

    public function test_la_autora_cambia_el_estilo_de_la_ficha_y_puede_volver_al_del_perfil(): void
    {
        $ficha = $this->fichaLista();
        $pagina = Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha]);

        $pagina->call('cambiarEstilo', 'caligrafia');

        $this->assertSame('caligrafia', $ficha->refresh()->estilo);
        $pagina->assertSeeHtml('t-caligrafia');

        $pagina->call('cambiarEstilo', '');

        $this->assertNull($ficha->refresh()->estilo);
    }

    public function test_un_estilo_que_no_existe_no_se_guarda(): void
    {
        $ficha = $this->fichaLista(['estilo' => 'esencial']);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('cambiarEstilo', 'atlas');

        $this->assertSame('esencial', $ficha->refresh()->estilo);
    }

    public function test_el_proyecto_usa_su_estilo_y_la_autora_lo_cambia(): void
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'estilo' => 'cartulina', 'contenido' => ['titulo' => 'Exploradores del planeta']]);

        $this->actingAs($proyecto->user)->get(route('proyectos.ver', $proyecto))
            ->assertOk()
            ->assertSee('doc-proyecto t-cartulina', false);

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('cambiarEstilo', 'esencial');

        $this->assertSame('esencial', $proyecto->refresh()->estilo);
    }

    public function test_otra_docente_no_puede_cambiar_el_estilo(): void
    {
        $ficha = $this->fichaLista();
        $otra = User::factory()->create();

        $pagina = Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha]);
        $this->actingAs($otra);

        $pagina->call('cambiarEstilo', 'cartulina')->assertForbidden();

        $this->assertNull($ficha->refresh()->estilo);
    }

    public function test_la_docente_guarda_su_estilo_en_el_perfil_sin_perder_otras_preferencias(): void
    {
        $user = User::factory()->create(['preferencias' => ['otra' => 'se queda']]);

        Volt::actingAs($user)->test('profile.update-estilo-form')
            ->assertSet('estilo', 'cuaderno')
            ->set('estilo', 'esencial')
            ->call('guardarEstilo')
            ->assertHasNoErrors()
            ->assertDispatched('estilo-guardado');

        $this->assertSame(['otra' => 'se queda', 'estilo' => 'esencial'], $user->refresh()->preferencias);
    }

    public function test_el_perfil_no_acepta_un_estilo_que_no_existe(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-estilo-form')
            ->set('estilo', 'atlas')
            ->call('guardarEstilo')
            ->assertHasErrors(['estilo' => 'in']);

        $this->assertNull($user->refresh()->preferencias);
    }
}

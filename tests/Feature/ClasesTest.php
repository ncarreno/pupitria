<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ClasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
    }

    public function test_la_pagina_requiere_sesion(): void
    {
        $this->get(route('clases'))->assertRedirect(route('login'));
    }

    public function test_una_docente_crea_su_clase_con_areas(): void
    {
        $docente = User::factory()->create();

        Volt::actingAs($docente)->test('pages.clases')
            ->set('nombre', '3.º A')
            ->set('curso', 3)
            ->set('alumnos', 17)
            ->set('alumnos_refuerzo', 3)
            ->set('alumnos_ampliacion', 2)
            ->set('areas', ['LCL', 'MAT', 'CN', 'CS', 'CA', 'EA'])
            ->call('guardar')
            ->assertHasNoErrors();

        $clase = $docente->clases()->with('areas')->sole();
        $this->assertSame(3, $clase->curso);
        $this->assertSame(17, $clase->alumnos);
        $this->assertEqualsCanonicalizing(['LCL', 'MAT', 'CN', 'CS', 'CA', 'EA'], $clase->areas->pluck('codigo')->all());
    }

    public function test_refuerzo_y_ampliacion_no_pueden_superar_el_total(): void
    {
        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->set('nombre', '3.º A')
            ->set('curso', 3)
            ->set('alumnos', 10)
            ->set('alumnos_refuerzo', 6)
            ->set('alumnos_ampliacion', 5)
            ->set('areas', ['LCL'])
            ->call('guardar')
            ->assertHasErrors('alumnos_ampliacion');

        $this->assertSame(0, Clase::count());
    }

    public function test_valores_civicos_solo_en_sexto(): void
    {
        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->set('nombre', '3.º A')
            ->set('curso', 3)
            ->set('alumnos', 20)
            ->set('areas', ['LCL', 'EVCE'])
            ->call('guardar')
            ->assertHasErrors('areas');
    }

    public function test_al_cambiar_de_curso_se_quitan_las_areas_que_no_existen(): void
    {
        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->set('curso', 6)
            ->set('areas', ['LCL', 'EVCE'])
            ->set('curso', 4)
            ->assertSet('areas', ['LCL']);
    }

    public function test_hay_que_elegir_al_menos_un_area(): void
    {
        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->set('nombre', '1.º B')
            ->set('areas', [])
            ->call('guardar')
            ->assertHasErrors('areas');
    }

    public function test_no_se_puede_editar_ni_borrar_la_clase_de_otra_docente(): void
    {
        $ajena = Clase::factory()->create();

        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->call('editar', $ajena->id)
            ->assertForbidden();

        Volt::actingAs(User::factory()->create())->test('pages.clases')
            ->set('borrandoId', $ajena->id)
            ->call('borrar')
            ->assertForbidden();

        $this->assertModelExists($ajena);
    }

    public function test_una_docente_borra_su_clase(): void
    {
        $clase = Clase::factory()->create();

        Volt::actingAs($clase->user)->test('pages.clases')
            ->call('confirmarBorrado', $clase->id)
            ->call('borrar');

        $this->assertModelMissing($clase);
    }

    public function test_el_inicio_invita_a_configurar_la_clase_y_luego_la_muestra(): void
    {
        $docente = User::factory()->create();
        $this->actingAs($docente)->get(route('dashboard'))->assertOk()->assertSee('Configurar mi clase');

        Clase::factory()->for($docente)->create(['nombre' => '3.º A']);
        $this->actingAs($docente)->get(route('dashboard'))->assertOk()->assertSee('3.º A')->assertDontSee('Configurar mi clase');
    }

    public function test_el_curso_escolar_cambia_en_septiembre(): void
    {
        Carbon::setTestNow('2026-08-31');
        $this->assertSame('2025/2026', Clase::cursoEscolarActual());

        Carbon::setTestNow('2026-09-01');
        $this->assertSame('2026/2027', Clase::cursoEscolarActual());
    }
}

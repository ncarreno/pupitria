<?php

namespace Tests\Feature;

use App\Models\Ficha;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpresionTest extends TestCase
{
    use RefreshDatabase;

    private function fichaLista(): Ficha
    {
        $nivel = fn (string $consigna) => ['consigna' => $consigna, 'bloques' => [
            ['tipo' => 'lectura', 'titulo' => 'El koala', 'enunciado' => '', 'texto' => 'El koala vive en Australia.'],
        ]];

        return Ficha::factory()->create([
            'estado' => 'listo',
            'estilo' => 'cartulina',
            'contenido' => ['refuerzo' => $nivel('Consigna de refuerzo'), 'estandar' => $nivel('Consigna estándar'), 'ampliacion' => $nivel('Consigna de ampliación')],
        ]);
    }

    public function test_la_autora_imprime_el_proyecto_con_su_estilo(): void
    {
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'estilo' => 'esencial', 'contenido' => ['titulo' => 'Exploradores del planeta']]);

        $this->actingAs($proyecto->user)->get(route('proyectos.imprimir', $proyecto))
            ->assertOk()
            ->assertSee('class="impresion', false)
            ->assertSee('doc-proyecto t-esencial', false)
            ->assertSee('Exploradores del planeta');
    }

    public function test_la_ficha_imprime_el_nivel_pedido(): void
    {
        $ficha = $this->fichaLista();

        $this->actingAs($ficha->user)->get(route('fichas.imprimir', [$ficha, 'nivel' => 'refuerzo']))
            ->assertOk()
            ->assertSee('hoja-ficha refuerzo t-cartulina', false)
            ->assertSee('Consigna de refuerzo')
            ->assertDontSee('Consigna estándar');
    }

    public function test_la_ficha_imprime_los_tres_niveles_en_orden(): void
    {
        $ficha = $this->fichaLista();

        $this->actingAs($ficha->user)->get(route('fichas.imprimir', [$ficha, 'nivel' => 'todos']))
            ->assertOk()
            ->assertSeeInOrder(['Consigna de refuerzo', 'Consigna estándar', 'Consigna de ampliación']);
    }

    public function test_un_nivel_que_no_existe_imprime_el_estandar(): void
    {
        $ficha = $this->fichaLista();

        $this->actingAs($ficha->user)->get(route('fichas.imprimir', [$ficha, 'nivel' => 'inventado']))
            ->assertOk()
            ->assertSee('Consigna estándar')
            ->assertDontSee('Consigna de refuerzo');
    }

    public function test_lo_que_se_esta_generando_no_se_imprime_y_vuelve_a_su_pagina(): void
    {
        $proyecto = Proyecto::factory()->create();
        $ficha = Ficha::factory()->create();

        $this->actingAs($proyecto->user)->get(route('proyectos.imprimir', $proyecto))
            ->assertRedirect(route('proyectos.ver', $proyecto));
        $this->actingAs($ficha->user)->get(route('fichas.imprimir', $ficha))
            ->assertRedirect(route('fichas.ver', $ficha));
    }

    public function test_otra_docente_no_imprime_lo_ajeno(): void
    {
        $otra = User::factory()->create();
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => ['titulo' => 'Exploradores del planeta']]);
        $ficha = $this->fichaLista();

        $this->actingAs($otra)->get(route('proyectos.imprimir', $proyecto))->assertForbidden();
        $this->actingAs($otra)->get(route('fichas.imprimir', $ficha))->assertForbidden();
    }

    public function test_sin_sesion_lleva_a_entrar(): void
    {
        $ficha = $this->fichaLista();

        $this->get(route('fichas.imprimir', $ficha))->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Criterio;
use App\Models\Descriptor;
use App\Models\Saber;
use App\Models\User;
use App\Services\Curriculo\CatalogoCurriculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('curriculo:importar')->assertSuccessful();
    }

    public function test_importa_el_curriculo_completo(): void
    {
        $this->assertSame(429, Criterio::count());
        $this->assertSame(865, Saber::count());
        $this->assertSame(34, Descriptor::count());
    }

    public function test_la_importacion_es_idempotente(): void
    {
        $this->artisan('curriculo:importar')->assertSuccessful();
        $this->assertSame(429, Criterio::count());
    }

    public function test_lo_que_ya_no_esta_en_el_json_se_avisa_y_solo_se_borra_si_se_pide(): void
    {
        $existente = Criterio::findOrFail('LCL.C2.4.1');
        $sobrante = Criterio::create([
            'id' => 'LCL.C2.99.1', 'competencia_especifica_id' => $existente->competencia_especifica_id,
            'area_id' => $existente->area_id, 'ciclo' => 2, 'codigo' => '99.1', 'texto' => 'Criterio retirado del decreto',
        ]);

        $this->artisan('curriculo:importar')
            ->expectsOutputToContain('Hay 1 criterios que ya no están en el JSON: LCL.C2.99.1')
            ->assertSuccessful();
        $this->assertModelExists($sobrante);

        $this->artisan('curriculo:importar', ['--borrar-sobrantes' => true])
            ->expectsOutputToContain('Borrados 1 criterios que ya no están en el JSON: LCL.C2.99.1')
            ->assertSuccessful();
        $this->assertModelMissing($sobrante);
        $this->assertSame(429, Criterio::count());
    }

    public function test_descarta_criterios_inventados_o_de_otro_ciclo(): void
    {
        [$validos, $descartados] = app(CatalogoCurriculo::class)
            ->filtrarCriterios(['LCL.C2.4.1', 'LCL.C9.9.9', 'MAT.C2.1.1', 'LCL.C1.4.1'], 2, ['LCL']);

        $this->assertSame(['LCL.C2.4.1'], $validos);
        $this->assertSame(3, $descartados);
    }

    public function test_un_id_repetido_cuenta_una_sola_vez(): void
    {
        [$validos, $descartados] = app(CatalogoCurriculo::class)
            ->filtrarCriterios(['LCL.C2.4.1', 'LCL.C2.4.1', 'LCL.C9.9.9', 'LCL.C9.9.9'], 2, ['LCL']);

        $this->assertSame(['LCL.C2.4.1'], $validos);
        $this->assertSame(1, $descartados);
    }

    public function test_calcula_descriptores_a_partir_de_criterios(): void
    {
        $d = app(CatalogoCurriculo::class)->derivados(['MAT.C2.1.1']);

        $this->assertSame(['CCEC4', 'CD2', 'CE1', 'CE3', 'CPSAA5', 'STEM1', 'STEM2', 'STEM4'], $d['descriptores']->pluck('codigo')->all());
    }

    public function test_la_pagina_de_curriculo_requiere_sesion_y_se_muestra(): void
    {
        $this->get('/curriculo')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/curriculo?area=MAT&ciclo=2')
            ->assertOk()
            ->assertSee('Interpretar, de forma verbal o gráfica');
    }
}

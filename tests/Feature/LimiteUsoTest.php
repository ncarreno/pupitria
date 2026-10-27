<?php

namespace Tests\Feature;

use App\Jobs\GenerarFicha;
use App\Jobs\GenerarProyecto;
use App\Models\Clase;
use App\Models\Ficha;
use App\Models\Generacion;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LimiteUsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-15 10:00:00');
        Queue::fake();
    }

    private function generaciones(User $user, int $cuantas, string $tarea = 'proyecto', string $estado = 'completada'): void
    {
        for ($i = 0; $i < $cuantas; $i++) {
            Generacion::create(['user_id' => $user->id, 'tarea' => $tarea, 'estado' => $estado]);
        }
    }

    private function fichaListaDe(User $user): Ficha
    {
        $nivel = ['consigna' => 'Lee y responde.', 'bloques' => []];

        return Ficha::factory()->create([
            'clase_id' => Clase::factory()->for($user)->create(['curso' => 3])->id,
            'estado' => 'listo',
            'contenido' => ['refuerzo' => $nivel, 'estandar' => $nivel, 'ampliacion' => $nivel],
        ]);
    }

    public function test_al_llegar_al_limite_no_se_crea_el_proyecto(): void
    {
        config(['uso.limite_mensual' => 2]);
        $clase = Clase::factory()->create(['curso' => 3]);
        $this->generaciones($clase->user, 2);

        Volt::actingAs($clase->user)->test('pages.proyectos.nuevo')
            ->set('claseId', $clase->id)
            ->set('idea', 'Un viaje por los continentes y sus animales')
            ->set('areas', ['LCL', 'CN'])
            ->call('crear')
            ->assertHasErrors('limite');

        $this->assertSame(0, Proyecto::count());
        Queue::assertNothingPushed();
    }

    public function test_al_llegar_al_limite_no_se_ajusta_el_proyecto(): void
    {
        config(['uso.limite_mensual' => 1]);
        $proyecto = Proyecto::factory()->create(['estado' => 'listo', 'contenido' => ['titulo' => 'Exploradores del planeta']]);
        $this->generaciones($proyecto->user, 1, 'ajuste');

        Volt::actingAs($proyecto->user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
            ->call('ajustar', 'Más manipulativo')
            ->assertHasErrors('limite');

        $this->assertSame('listo', $proyecto->refresh()->estado);
        Queue::assertNothingPushed();
    }

    public function test_las_fallidas_la_ilustracion_y_el_mes_pasado_no_cuentan(): void
    {
        config(['uso.limite_mensual' => 1]);
        $ficha = $this->fichaListaDe(User::factory()->create());
        $this->generaciones($ficha->user, 1, 'ficha', 'fallida');
        $this->generaciones($ficha->user, 1, 'imagen');
        $this->generaciones($ficha->user, 1, 'prueba');
        Generacion::create(['user_id' => $ficha->user_id, 'tarea' => 'ficha', 'estado' => 'completada'])
            ->forceFill(['created_at' => '2026-09-30 23:00:00'])->save();

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('regenerar')
            ->assertHasNoErrors();

        Queue::assertPushed(GenerarFicha::class);
    }

    public function test_lo_que_se_esta_generando_cuenta_aunque_siga_en_la_cola(): void
    {
        config(['uso.limite_mensual' => 1]);
        $ficha = $this->fichaListaDe(User::factory()->create());
        Proyecto::factory()->create(['clase_id' => $ficha->clase_id, 'estado' => 'generando']);

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('regenerar')
            ->assertHasErrors('limite');

        Queue::assertNothingPushed();
    }

    public function test_la_cuenta_sin_limite_y_el_administrador_generan_aunque_pasen_del_limite(): void
    {
        config(['uso.limite_mensual' => 1]);

        foreach ([User::factory()->sinLimite()->create(), User::factory()->admin()->create()] as $user) {
            $proyecto = Proyecto::factory()->create([
                'clase_id' => Clase::factory()->for($user)->create(['curso' => 3])->id,
                'estado' => 'listo',
                'contenido' => ['titulo' => 'Exploradores del planeta'],
            ]);
            $this->generaciones($user, 3);

            Volt::actingAs($user)->test('pages.proyectos.ver', ['proyecto' => $proyecto])
                ->call('ajustar', 'Más manipulativo')
                ->assertHasNoErrors();
        }

        Queue::assertPushed(GenerarProyecto::class, 2);
    }

    public function test_sin_limite_configurado_no_hay_limite(): void
    {
        config(['uso.limite_mensual' => null]);
        $ficha = $this->fichaListaDe(User::factory()->create());
        $this->generaciones($ficha->user, 50, 'ficha');

        Volt::actingAs($ficha->user)->test('pages.fichas.ver', ['ficha' => $ficha])
            ->call('regenerar')
            ->assertHasNoErrors();

        Queue::assertPushed(GenerarFicha::class);
    }

    public function test_la_docente_ve_cuantas_le_quedan_y_cuando_se_reinicia(): void
    {
        config(['uso.limite_mensual' => 5]);
        $clase = Clase::factory()->create(['curso' => 3]);
        $this->generaciones($clase->user, 2);

        $this->actingAs($clase->user)->get(route('proyectos.nuevo'))
            ->assertOk()
            ->assertSee('Te quedan 3 generaciones de 5 este mes.');

        $this->generaciones($clase->user, 3);

        $this->actingAs($clase->user)->get(route('proyectos.nuevo'))
            ->assertOk()
            ->assertSee('Has usado las 5 generaciones de este mes. Podrás generar de nuevo el 1 de noviembre.');
    }

    public function test_el_administrador_quita_y_pone_el_limite_a_una_cuenta(): void
    {
        $docente = User::factory()->create();
        $pagina = Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas');

        $pagina->call('alternarSinLimite', $docente->id);

        $this->assertTrue($docente->refresh()->sin_limite);

        $pagina->call('alternarSinLimite', $docente->id);

        $this->assertFalse($docente->refresh()->sin_limite);
    }

    public function test_una_cuenta_nueva_se_puede_crear_sin_limite(): void
    {
        Volt::actingAs(User::factory()->admin()->create())->test('pages.admin.cuentas')
            ->call('nueva')
            ->set('nombre', 'Lucía Fernández')
            ->set('email', 'lucia@colegio.es')
            ->set('sinLimite', true)
            ->call('crear')
            ->assertHasNoErrors();

        $this->assertTrue(User::where('email', 'lucia@colegio.es')->sole()->sin_limite);
    }
}

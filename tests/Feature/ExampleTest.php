<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_la_portada_esta_en_espanol_y_sin_contenido_de_laravel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Crear cuenta')
            ->assertSee('Entrar')
            ->assertDontSee('Laracasts');
    }
}

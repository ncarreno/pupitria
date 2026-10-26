<?php

namespace Tests\Feature;

use App\Models\Generacion;
use App\Models\User;
use App\Services\IA\ClienteIA;
use Database\Seeders\IaConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Tests\TestCase;

class ImagenesTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_la_imagen_con_openai_sin_texto_y_la_guarda(): void
    {
        Storage::fake('public');
        $this->seed(IaConfiguracionSeeder::class);
        Image::fake();
        $docente = User::factory()->create();

        $r = app(ClienteIA::class)->generarImagen('Un koala en una rama', 'portrait', $docente);

        Storage::disk('public')->assertExists($r['ruta']);
        $this->assertStringStartsWith("imagenes/{$docente->id}/", $r['ruta']);
        Image::assertGenerated(fn ($prompt) => str_contains($prompt->prompt, 'Un koala en una rama')
            && str_contains($prompt->prompt, 'sin ningún texto'));
        $this->assertSame('completada', Generacion::sole()->estado);
        $this->assertSame('imagen', Generacion::sole()->tarea);
    }
}

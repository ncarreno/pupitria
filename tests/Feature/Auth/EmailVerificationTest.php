<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response
            ->assertSeeVolt('pages.auth.verify-email')
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_sin_verificar_el_email_no_se_entra_en_la_app(): void
    {
        $user = User::factory()->unverified()->create();

        foreach (['dashboard', 'clases', 'proyectos', 'proyectos.nuevo', 'fichas', 'fichas.nueva', 'curriculo'] as $ruta) {
            $this->actingAs($user)->get(route($ruta))->assertRedirect(route('verification.notice'));
        }
    }

    public function test_al_registrarse_se_envia_el_email_de_verificacion_en_espanol(): void
    {
        Notification::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'Lucía')
            ->set('email', 'lucia@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'lucia@example.com')->sole();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notificacion) use ($user) {
            $correo = $notificacion->toMail($user);

            return $correo->subject === 'Confirma tu email' && $correo->actionText === 'Confirmar mi email';
        });
    }
}

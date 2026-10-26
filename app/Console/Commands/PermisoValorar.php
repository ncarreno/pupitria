<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('usuarios:valorar {email : Email de un usuario ya registrado} {--quitar : Quita el permiso}')]
#[Description('Da (o quita) el permiso para ver y valorar la batería de pruebas sin ser administrador')]
class PermisoValorar extends Command implements PromptsForMissingInput
{
    /**
     * Si no se pasa el email, se pregunta.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return ['email' => '¿Email de quien va a valorar las pruebas?'];
    }

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (! $usuario) {
            $this->error('No hay ningún usuario con ese email. Tiene que registrarse primero en la aplicación.');

            return self::FAILURE;
        }

        $puede = ! $this->option('quitar');
        $usuario->forceFill(['puede_valorar' => $puede])->save();
        $this->info($puede ? "{$usuario->email} ya puede valorar las pruebas." : "{$usuario->email} ya no puede valorar las pruebas.");

        return self::SUCCESS;
    }
}

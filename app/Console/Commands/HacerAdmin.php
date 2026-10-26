<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('usuarios:admin {email : Email de un usuario ya registrado}')]
#[Description('Da el rol de administrador a un usuario (puede configurar la IA)')]
class HacerAdmin extends Command implements PromptsForMissingInput
{
    /**
     * Si no se pasa el email, se pregunta.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return ['email' => '¿Email del usuario que será administrador?'];
    }

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (! $usuario) {
            $this->error('No hay ningún usuario con ese email. Regístrate primero en la aplicación.');

            return self::FAILURE;
        }

        $usuario->forceFill(['rol' => 'admin'])->save();
        $this->info("{$usuario->email} ya es administrador.");

        return self::SUCCESS;
    }
}

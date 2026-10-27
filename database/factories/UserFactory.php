<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuario con rol de administrador.
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['rol' => 'admin'])->save());
    }

    /**
     * Docente con permiso para valorar la batería de pruebas (la beta tester).
     */
    public function valoradora(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['puede_valorar' => true])->save());
    }

    /**
     * Docente sin límite mensual de generaciones.
     */
    public function sinLimite(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['sin_limite' => true])->save());
    }
}

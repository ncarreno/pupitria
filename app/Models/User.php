<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'preferencias'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferencias' => 'array',
        ];
    }

    public function clases(): HasMany
    {
        return $this->hasMany(Clase::class)->orderByDesc('curso_escolar')->orderBy('curso')->orderBy('nombre');
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class)->latest('updated_at');
    }

    public function fichas(): HasMany
    {
        return $this->hasMany(Ficha::class)->latest('updated_at');
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'admin';
    }
}

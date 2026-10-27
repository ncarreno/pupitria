<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'preferencias'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'puede_valorar' => 'boolean',
        ];
    }

    /**
     * Las fichas se borran en cascada en la base de datos, sin eventos, así que sus imágenes se borran aquí.
     */
    protected static function booted(): void
    {
        static::deleted(fn (User $user) => Storage::disk('public')->deleteDirectory('imagenes/'.$user->id));
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

    /**
     * Ve y valora la batería de pruebas: el administrador y quien tenga el permiso (la beta tester).
     * Lanzar pruebas sigue siendo solo del administrador, porque gasta saldo.
     */
    public function puedeValorarPruebas(): bool
    {
        return $this->esAdministrador() || $this->puede_valorar;
    }

    /**
     * Entra por `/admin/login`: el administrador, y quien valora pruebas, que allí solo ve la batería.
     */
    public function puedeEntrarEnAdministracion(): bool
    {
        return $this->puedeValorarPruebas();
    }
}

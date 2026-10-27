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
            'sin_limite' => 'boolean',
        ];
    }

    /**
     * Las tareas de `generaciones` que cuentan para el límite mensual. La ilustración va con su ficha;
     * las pruebas y la batería son del administrador.
     */
    public const TAREAS_CON_LIMITE = ['proyecto', 'ajuste', 'ficha'];

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

    public function generaciones(): HasMany
    {
        return $this->hasMany(Generacion::class);
    }

    /**
     * Generaciones al mes que puede hacer, o null si no tiene límite (administrador, cuenta marcada
     * "sin límite" o `LIMITE_GENERACIONES_MES` vacío).
     */
    public function limiteMensual(): ?int
    {
        $limite = config('uso.limite_mensual');

        if ($this->esAdministrador() || $this->sin_limite || $limite === null || $limite === '') {
            return null;
        }

        return max(0, (int) $limite);
    }

    /**
     * Las completadas este mes (las fallidas no cuentan) más los documentos que se están generando:
     * su generación no queda registrada hasta que el job empieza, y así no se cuelan varias seguidas.
     */
    public function generacionesEsteMes(): int
    {
        return $this->generaciones()
            ->whereIn('tarea', self::TAREAS_CON_LIMITE)
            ->where('estado', 'completada')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count()
            + $this->proyectos()->where('estado', 'generando')->count()
            + $this->fichas()->where('estado', 'generando')->count();
    }

    /**
     * Null si no tiene límite.
     */
    public function generacionesRestantes(): ?int
    {
        $limite = $this->limiteMensual();

        return $limite === null ? null : max(0, $limite - $this->generacionesEsteMes());
    }

    public function puedeGenerar(): bool
    {
        return $this->generacionesRestantes() !== 0;
    }

    public function mensajeLimite(): string
    {
        return "Has usado las {$this->limiteMensual()} generaciones de este mes. Podrás generar de nuevo el "
            .now()->addMonthNoOverflow()->startOfMonth()->locale('es')->translatedFormat('j \d\e F').'.';
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

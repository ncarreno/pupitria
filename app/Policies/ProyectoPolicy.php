<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;

/**
 * Cada docente solo ve y modifica sus propios proyectos.
 */
class ProyectoPolicy
{
    public function view(User $user, Proyecto $proyecto): bool
    {
        return $proyecto->user_id === $user->id;
    }

    public function update(User $user, Proyecto $proyecto): bool
    {
        return $proyecto->user_id === $user->id;
    }

    public function delete(User $user, Proyecto $proyecto): bool
    {
        return $proyecto->user_id === $user->id;
    }
}

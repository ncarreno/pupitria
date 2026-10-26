<?php

namespace App\Policies;

use App\Models\Clase;
use App\Models\User;

/**
 * Cada docente solo ve y modifica sus propias clases.
 */
class ClasePolicy
{
    public function view(User $user, Clase $clase): bool
    {
        return $clase->user_id === $user->id;
    }

    public function update(User $user, Clase $clase): bool
    {
        return $clase->user_id === $user->id;
    }

    public function delete(User $user, Clase $clase): bool
    {
        return $clase->user_id === $user->id;
    }
}

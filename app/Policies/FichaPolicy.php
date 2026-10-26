<?php

namespace App\Policies;

use App\Models\Ficha;
use App\Models\User;

/**
 * Cada docente solo ve y modifica sus propias fichas.
 */
class FichaPolicy
{
    public function view(User $user, Ficha $ficha): bool
    {
        return $ficha->user_id === $user->id;
    }

    public function update(User $user, Ficha $ficha): bool
    {
        return $ficha->user_id === $user->id;
    }

    public function delete(User $user, Ficha $ficha): bool
    {
        return $ficha->user_id === $user->id;
    }
}

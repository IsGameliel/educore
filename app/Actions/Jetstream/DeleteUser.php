<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    public function delete(User $user): void
    {
        // Revoke access while preserving institutional records and identity.
        $user->delete();
    }
}

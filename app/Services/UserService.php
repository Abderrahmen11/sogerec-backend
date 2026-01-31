<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Get all technicians.
     */
    public function getTechnicians()
    {
        return User::where('role', 'technician')->orderBy('name')->get();
    }

    /**
     * Find a user by id or null.
     */
    public function findUser(?int $id): ?User
    {
        if (!$id) {
            return null;
        }
        return User::find($id);
    }
}

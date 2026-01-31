<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    /**
     * Create a personal access token for a user and return plain text token.
     */
    public function createTokenForUser(User $user, string $name = 'auth_token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }
}

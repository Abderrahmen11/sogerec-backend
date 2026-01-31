<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Intervention;

class InterventionPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        //
    }

    public function view(User $user, Intervention $intervention): bool
    {
        if ($user->role === 'admin') return true;
        if ($user->role === 'technician' && $intervention->user_id === $user->id) return true;
        if ($user->role === 'client' && $intervention->ticket && $intervention->ticket->user_id === $user->id) return true;
        return false;
    }

    public function update(User $user, Intervention $intervention): bool
    {
        if ($user->role === 'admin') return true;
        if ($user->role === 'technician' && $intervention->user_id === $user->id) return true;
        return false;
    }

    public function delete(User $user, Intervention $intervention): bool
    {
        return $user->role === 'admin';
    }
}

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

    public function viewAny(User $user): bool
    {
        // Allow admins and technicians to list interventions
        // Clients should not list interventions directly (only within tickets)
        return in_array($user->role, ['admin', 'technician']);
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

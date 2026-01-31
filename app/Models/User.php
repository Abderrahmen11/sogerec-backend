<?php

namespace App\Models;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is technician
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Get the interventions assigned to this user (technician).
     */
    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }

    /**
     * Get tickets created by this user (client).
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get reports submitted by this user (technician).
     */
    public function reports()
    {
        return $this->hasMany(InterventionReport::class, 'technician_id');
    }
}

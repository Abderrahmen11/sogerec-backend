<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['title', 'description', 'status', 'scheduled_at', 'completed_at', 'ticket_id', 'user_id', 'location', 'latitude', 'longitude'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Technician assigned to this intervention (user_id = technician_id).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - technician assigned to this intervention.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reports()
    {
        return $this->hasMany(InterventionReport::class);
    }

    public function report()
    {
        return $this->hasOne(InterventionReport::class)->latestOfMany();
    }

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }
}

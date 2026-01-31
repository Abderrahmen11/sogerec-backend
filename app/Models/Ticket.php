<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description', 'priority', 'status', 'category', 'assigned_to', 'cancellation_reason'];

    /**
     * Appended attribute for API compatibility - ensures frontend receives assigned technician.
     */
    protected $appends = ['assigned_to_user'];

    public function getAssignedToUserAttribute()
    {
        return $this->assignedTo;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }
}

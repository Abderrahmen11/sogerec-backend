<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $fillable = ['intervention_id', 'technician_id', 'planned_date', 'status'];

    protected $casts = [
        'planned_date' => 'date',
    ];

    public function intervention()
    {
        return $this->belongsTo(Intervention::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}

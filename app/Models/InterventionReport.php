<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterventionReport extends Model
{
    use HasFactory;

    protected $fillable = ['intervention_id', 'content', 'status'];

    public function intervention()
    {
        return $this->belongsTo(Intervention::class);
    }

    protected $guarded = [];
}

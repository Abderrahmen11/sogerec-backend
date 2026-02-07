<?php

namespace App\Repositories;

use App\Models\Intervention;

class InterventionRepository
{
    public function find(int $id): ?Intervention
    {
        return Intervention::with(['ticket', 'user', 'reports'])->find($id);
    }

    public function listForTechnician(int $technicianId)
    {
        return Intervention::with(['ticket', 'user'])->where('user_id', $technicianId)->orderBy('scheduled_at', 'asc')->get();
    }
}

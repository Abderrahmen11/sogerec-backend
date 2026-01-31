<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\Planning;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterventionService
{
    /**
     * Assign a technician to an intervention and optionally schedule it.
     */
    public function assignTechnician(Intervention $intervention, int $technicianId, ?string $scheduledAt = null): Intervention
    {
        return DB::transaction(function () use ($intervention, $technicianId, $scheduledAt) {
            $intervention->update([
                'user_id' => $technicianId,
                'scheduled_at' => $scheduledAt,
                'status' => $scheduledAt ? Intervention::STATUS_SCHEDULED : Intervention::STATUS_PENDING,
            ]);

            // update ticket assignment and status
            if ($intervention->ticket) {
                $intervention->ticket->update([
                    'assigned_to' => $technicianId,
                    'status' => 'assigned',
                ]);
            }

            if ($scheduledAt) {
                Planning::updateOrCreate(
                    ['intervention_id' => $intervention->id],
                    ['technician_id' => $technicianId, 'planned_date' => \Carbon\Carbon::parse($scheduledAt)->toDateString(), 'status' => 'scheduled']
                );
            }

            return $intervention->fresh(['ticket', 'user']);
        });
    }

    /**
     * Lightweight wrapper to update intervention status with safety checks.
     */
    public function updateStatus(Intervention $intervention, string $status): Intervention
    {
        try {
            $intervention->update(['status' => $status]);
            return $intervention->fresh(['ticket', 'user']);
        } catch (\Throwable $e) {
            Log::error('InterventionService::updateStatus failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

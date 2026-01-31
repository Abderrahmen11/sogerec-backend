<?php

namespace App\Listeners;

use App\Events\InterventionAssigned;
use App\Notifications\InterventionAssignedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Runs synchronously so technician receives notification immediately (no queue worker required).
 * Safeguard: Only notify if technician_id is set and valid.
 */
class NotifyTechnicianOfAssignment
{
    public function __invoke(InterventionAssigned $event): void
    {
        $intervention = $event->intervention;

        // Prevent duplicate notifications with strict validation
        if (!$intervention->user_id) {
            Log::warning('NotifyTechnicianOfAssignment: No technician assigned', [
                'intervention_id' => $intervention->id
            ]);
            return;
        }

        $technician = $intervention->user;
        if (!$technician) {
            Log::warning('NotifyTechnicianOfAssignment: Technician not found', [
                'intervention_id' => $intervention->id,
                'technician_id' => $intervention->user_id
            ]);
            return;
        }

        // Only notify technicians
        if ($technician->role !== 'technician') {
            Log::warning('NotifyTechnicianOfAssignment: User is not a technician', [
                'intervention_id' => $intervention->id,
                'user_id' => $technician->id,
                'role' => $technician->role
            ]);
            return;
        }

        try {
            // Prevent duplicate notification records for the same intervention
            $existing = $technician->notifications()->where('type', InterventionAssignedNotification::class)->get()->first(function ($n) use ($intervention) {
                return data_get($n, 'data.intervention_id') == $intervention->id;
            });

            if ($existing) {
                Log::info('NotifyTechnicianOfAssignment: Duplicate exists, skipping', [
                    'intervention_id' => $intervention->id,
                    'technician_id' => $technician->id
                ]);
            } else {
                $technician->notify(new InterventionAssignedNotification($intervention));
                Log::info('NotifyTechnicianOfAssignment: Notification sent', [
                    'intervention_id' => $intervention->id,
                    'technician_id' => $technician->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NotifyTechnicianOfAssignment: Failed to send notification', [
                'intervention_id' => $intervention->id,
                'technician_id' => $technician->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

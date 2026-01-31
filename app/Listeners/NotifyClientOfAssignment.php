<?php

namespace App\Listeners;

use App\Events\InterventionAssigned;
use App\Notifications\InterventionScheduledNotification;
use Illuminate\Support\Facades\Log;

/**
 * Runs synchronously so client receives notification immediately (no queue worker required).
 * Safeguard: Only notify if ticket and client exist and have valid data.
 */
class NotifyClientOfAssignment
{
    public function __invoke(InterventionAssigned $event): void
    {
        $intervention = $event->intervention->load('ticket.user');

        // Prevent duplicate notifications with strict validation
        if (!$intervention->ticket) {
            Log::warning('NotifyClientOfAssignment: Intervention has no ticket', [
                'intervention_id' => $intervention->id
            ]);
            return;
        }

        $client = $intervention->ticket->user;
        if (!$client) {
            Log::warning('NotifyClientOfAssignment: Ticket has no user/client', [
                'intervention_id' => $intervention->id,
                'ticket_id' => $intervention->ticket->id
            ]);
            return;
        }

        // Only notify clients
        if ($client->role !== 'client' && $client->role !== 'user') {
            Log::warning('NotifyClientOfAssignment: Ticket user is not a client', [
                'intervention_id' => $intervention->id,
                'user_id' => $client->id,
                'role' => $client->role
            ]);
            return;
        }

        try {
            // Prevent duplicate notification records for the same intervention
            $existing = $client->notifications()->where('type', InterventionScheduledNotification::class)->get()->first(function ($n) use ($intervention) {
                return data_get($n, 'data.intervention_id') == $intervention->id;
            });

            if ($existing) {
                Log::info('NotifyClientOfAssignment: Duplicate exists, skipping', [
                    'intervention_id' => $intervention->id,
                    'client_id' => $client->id
                ]);
            } else {
                $client->notify(new InterventionScheduledNotification($intervention));
                Log::info('NotifyClientOfAssignment: Notification sent', [
                    'intervention_id' => $intervention->id,
                    'client_id' => $client->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NotifyClientOfAssignment: Failed to send notification', [
                'intervention_id' => $intervention->id,
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\InterventionStatusChanged;
use App\Notifications\InterventionStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyClientOfStatusChange
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InterventionStatusChanged $event): void
    {
        $intervention = $event->intervention;
        if (!$intervention) {
            Log::warning('NotifyClientOfStatusChange: no intervention provided on event');
            return;
        }

        $intervention->loadMissing('ticket.user');
        if (!$intervention->ticket || !$intervention->ticket->user) {
            Log::warning('NotifyClientOfStatusChange: intervention has no ticket or client', ['intervention_id' => $intervention->id]);
            return;
        }

        $client = $intervention->ticket->user;
        try {
            $message = "Your intervention #{$intervention->id} status changed to " . ($event->newStatus ?? $intervention->status);
            $client->notify(new InterventionStatusUpdatedNotification($intervention, $message));
        } catch (\Exception $e) {
            Log::error('NotifyClientOfStatusChange: failed to notify client', ['error' => $e->getMessage(), 'intervention_id' => $intervention->id]);
        }
    }
}

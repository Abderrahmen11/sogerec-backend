<?php

namespace App\Listeners;

use App\Events\InterventionStatusChanged;
use App\Notifications\InterventionStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class NotifyAdminOfStatusChange
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
            Log::warning('NotifyAdminOfStatusChange: no intervention provided on event');
            return;
        }

        $message = "Intervention #{$intervention->id} status changed to " . ($event->newStatus ?? $intervention->status);
        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                foreach ($admins as $admin) {
                    $admin->notify(new InterventionStatusUpdatedNotification($intervention, $message));
                }
            }
        } catch (\Exception $e) {
            Log::error('NotifyAdminOfStatusChange: failed to notify admins', ['error' => $e->getMessage(), 'intervention_id' => $intervention->id]);
        }
    }
}

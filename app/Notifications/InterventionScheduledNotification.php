<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously so client receives notification immediately (no queue worker required).
 */
class InterventionScheduledNotification extends Notification
{
    use Queueable;

    protected $intervention;

    public function __construct(Intervention $intervention)
    {
        $this->intervention = $intervention;
    }

    public function via($notifiable)
    {
        return ['database']; // Database only - ensures notification is stored without queue/broadcast
    }

    public function toArray($notifiable)
    {
        $ticketId = $this->intervention->ticket_id ?? $this->intervention->ticket?->id ?? 'N/A';
        return [
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "A technician has been assigned to your ticket #{$ticketId}.",
            'type' => 'intervention_scheduled',
        ];
    }

    public function toBroadcast($notifiable)
    {
        $ticketId = $this->intervention->ticket_id ?? $this->intervention->ticket?->id ?? 'N/A';
        return new BroadcastMessage([
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "A technician has been assigned to your ticket #{$ticketId}.",
            'type' => 'intervention_scheduled',
        ]);
    }
}

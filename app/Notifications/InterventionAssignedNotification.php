<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously so technician receives notification immediately (no queue worker required).
 */
class InterventionAssignedNotification extends Notification
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
        return [
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "You have been assigned a new intervention for Ticket #{$this->intervention->ticket_id}",
            'type' => 'intervention_assigned',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "You have been assigned a new intervention for Ticket #{$this->intervention->ticket_id}",
            'type' => 'intervention_assigned',
        ]);
    }
}

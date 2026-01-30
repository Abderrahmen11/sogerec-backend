<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InterventionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $intervention;

    public function __construct(Intervention $intervention)
    {
        $this->intervention = $intervention;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "A technician has been assigned to your ticket #{$this->intervention->ticket->id}.",
            'type' => 'intervention_scheduled',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'message' => "A technician has been assigned to your ticket #{$this->intervention->ticket->id}.",
            'type' => 'intervention_scheduled',
        ]);
    }
}

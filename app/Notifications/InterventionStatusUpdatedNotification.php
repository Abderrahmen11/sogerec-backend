<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InterventionStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $intervention;
    protected $message;

    public function __construct(Intervention $intervention, $message)
    {
        $this->intervention = $intervention;
        $this->message = $message;
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
            'status' => $this->intervention->status,
            'message' => $this->message,
            'type' => 'intervention_status_updated',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'intervention_id' => $this->intervention->id,
            'ticket_id' => $this->intervention->ticket_id,
            'status' => $this->intervention->status,
            'message' => $this->message,
            'type' => 'intervention_status_updated',
        ]);
    }
}

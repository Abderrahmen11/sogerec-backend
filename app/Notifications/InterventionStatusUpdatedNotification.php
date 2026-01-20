<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InterventionStatusUpdatedNotification extends Notification
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
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'intervention_id' => $this->intervention->id,
            'status' => $this->intervention->status,
            'message' => $this->message,
            'type' => 'intervention_status_updated',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $message,
        public string $type = 'ticket_status'
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}

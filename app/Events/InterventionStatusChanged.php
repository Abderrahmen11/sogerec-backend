<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Intervention;

class InterventionStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?Intervention $intervention;
    public ?string $oldStatus;
    public ?string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(?Intervention $intervention = null, ?string $oldStatus = null, ?string $newStatus = null)
    {
        $this->intervention = $intervention;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        if ($this->intervention) {
            return [new PrivateChannel("intervention.{$this->intervention->id}")];
        }
        return [new PrivateChannel('interventions')];
    }
}

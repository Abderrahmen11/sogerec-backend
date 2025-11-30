<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TicketResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\InterventionReportResource;

class InterventionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'ticket_id' => $this->ticket_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'user' => new UserResource($this->whenLoaded('user')),
            'reports' => InterventionReportResource::collection($this->whenLoaded('reports')),
        ];
    }
}

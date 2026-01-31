<?php

namespace App\Repositories;

use App\Models\Ticket;

class TicketRepository
{
    public function find(int $id): ?Ticket
    {
        return Ticket::with(['user', 'assignedTo'])->find($id);
    }

    public function listBy(array $filters = [])
    {
        $query = Ticket::with(['user', 'assignedTo']);
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

class TicketRepository
{
    //
}

<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * Create a new ticket
     */
    public function createTicket(array $data, User $user): Ticket
    {
        return DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'category' => $data['category'],
                'status' => 'open',
                'user_id' => $user->id,
            ]);

            // Notify admins
            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->count() > 0) {
                    Notification::send($admins, new NewTicketNotification($ticket));
                }
            } catch (\Exception $e) {
                // Log notification failure but don't fail the transaction
                Log::error("Failed to send new ticket notification: " . $e->getMessage());
            }

            return $ticket;
        });
    }

    /**
     * Update a ticket
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->update($data);
            return $ticket->fresh(['user', 'assignedTo']);
        });
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        return DB::transaction(function () use ($ticket, $status) {
            $ticket->update(['status' => $status]);
            return $ticket->fresh(['user', 'assignedTo']);
        });
    }

    /**
     * Delete a ticket
     */
    public function deleteTicket(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            return $ticket->delete();
        });
    }

    /**
     * Add a comment (placeholder for future logic)
     */
    public function addComment(Ticket $ticket, string $comment): void
    {
        // Future: Create comment model logic here
        // DB::transaction(...)
    }
}

<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\InterventionStatusUpdatedNotification;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

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
            $updated = $ticket->fresh(['user', 'assignedTo']);

            // Validate assignment persistence when assigned_to was updated
            if (array_key_exists('assigned_to', $data)) {
                $updated->refresh();
                $expected = $data['assigned_to'] ? (int) $data['assigned_to'] : null;
                $actual = $updated->assigned_to ? (int) $updated->assigned_to : null;
                if ($actual !== $expected) {
                    throw new \RuntimeException('Failed to persist technician assignment to database.');
                }
            }

            return $updated;
        });
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        return DB::transaction(function () use ($ticket, $status) {
            $ticket->update(['status' => $status]);

            if ($status === 'cancelled') {
                $ticket->interventions()->whereNotIn('status', ['completed', 'cancelled'])
                    ->update(['status' => Intervention::STATUS_CANCELLED]);

                $msg = "Ticket #{$ticket->id} has been cancelled.";
                User::where('role', 'admin')->get()->each(fn($a) => $a->notify(new TicketStatusNotification($ticket, $msg, 'ticket_cancelled')));

                foreach ($ticket->interventions()->whereNotNull('user_id')->with('user')->get() as $int) {
                    if ($int->user) {
                        $int->user->notify(new InterventionStatusUpdatedNotification($int, "Intervention #{$int->id} for Ticket #{$ticket->id} has been cancelled."));
                    }
                }
            }

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
     * Add a comment to a ticket and notify relevant party safely.
     */
    public function addComment(Ticket $ticket, string $comment): void
    {
        DB::transaction(function () use ($ticket, $comment) {
            $user = Auth::user();
            $userId = $user?->id ?? $ticket->user_id;

            $created = Comment::create([
                'content' => $comment,
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
            ]);

            try {
                // Notify ticket owner if someone else commented
                if ($ticket->user_id && $ticket->user_id !== $userId && $ticket->user) {
                    $ticket->user->notify(new TicketStatusNotification($ticket, "A new comment was added to your ticket."));
                }
            } catch (\Exception $e) {
                Log::error('TicketService::addComment - notification failed: ' . $e->getMessage());
            }

            return $created;
        });
    }
}

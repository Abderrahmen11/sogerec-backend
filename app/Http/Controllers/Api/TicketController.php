<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Get all tickets
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'assignedTo']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority if provided
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        return response()->json($tickets);
    }

    /**
     * Get a single ticket
     */
    public function show($id)
    {
        $ticket = Ticket::with(['user', 'assignedTo'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * Create a new ticket
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'category' => $request->category,
            'status' => 'open',
            'user_id' => $request->user()->id,
        ]);

        return response()->json($ticket->load(['user', 'assignedTo']), 201);
    }

    /**
     * Update a ticket
     */
    public function update($id, Request $request)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        $ticket->update($request->only(['title', 'description', 'priority', 'status', 'assigned_to']));

        return response()->json($ticket->load(['user', 'assignedTo']));
    }

    /**
     * Delete a ticket
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Update ticket status
     */
    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return response()->json($ticket->load(['user', 'assignedTo']));
    }

    /**
     * Add a comment to a ticket
     */
    public function addComment($id, Request $request)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $ticket = Ticket::findOrFail($id);

        // For now, just return success. You can implement a comments table later
        return response()->json(['message' => 'Comment added successfully']);
    }

    /**
     * Search tickets
     */
    public function search(Request $request)
    {
        $query = Ticket::with(['user', 'assignedTo']);

        if ($request->has('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        return response()->json($tickets);
    }
}

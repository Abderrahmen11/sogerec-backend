<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Get all tickets
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user', 'assignedTo']);

        // Apply role-based filtering
        $this->applyRoleFilters($query, $user);

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
    public function show(Request $request, $id)
    {
        $ticket = Ticket::with(['user', 'assignedTo'])->findOrFail($id);

        // Authorization check
        $this->authorizeTicketAccess($ticket, $request->user());

        $response = $ticket->toArray();
        // Ensure frontend receives explicit assignment data
        $response['assigned_to_user_id'] = $ticket->assigned_to;
        $response['technician_name'] = $ticket->assignedTo?->name;
        return response()->json($response);
    }

    /**
     * Create a new ticket
     */
    protected $ticketService;

    public function __construct(\App\Services\TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
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

        $ticket = $this->ticketService->createTicket($request->all(), $request->user());

        return response()->json($ticket->load(['user', 'assignedTo']), 201);
    }

    /**
     * Update a ticket
     */
    public function update($id, Request $request)
    {
        $ticket = Ticket::findOrFail($id);

        // Authorization check
        $this->authorizeTicketAction($ticket, $request->user());

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,in_progress,resolved,closed,cancelled',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'cancellation_reason' => 'sometimes|string|nullable',
        ]);

        // Validate technician role when assigning (Admin only usually, but controller logic allows it)
        if ($request->has('assigned_to') && $request->assigned_to) {
            $technician = User::find($request->assigned_to);
            if (!$technician || $technician->role !== 'technician') {
                return response()->json([
                    'message' => 'Assigned user must have technician role.',
                    'error' => 'INVALID_TECHNICIAN'
                ], 422);
            }
        }

        $updatedTicket = $this->ticketService->updateTicket($ticket, $request->only([
            'title',
            'description',
            'priority',
            'status',
            'assigned_to',
            'cancellation_reason'
        ]));

        $response = $updatedTicket->toArray();
        $response['assigned_to_user_id'] = $updatedTicket->assigned_to;
        $response['technician_name'] = $updatedTicket->assignedTo?->name;

        return response()->json($response);
    }

    /**
     * Delete a ticket
     */
    public function destroy(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Only owner or admin can delete
        if ($request->user()->role !== 'admin' && $ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->ticketService->deleteTicket($ticket);

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Update ticket status
     */
    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,resolved,closed,cancelled',
        ]);

        $ticket = Ticket::findOrFail($id);

        // Authorization check
        $this->authorizeTicketAccess($ticket, $request->user());

        $updatedTicket = $this->ticketService->updateStatus($ticket, $request->status);

        return response()->json($updatedTicket);
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

        // Authorization check
        $this->authorizeTicketAccess($ticket, $request->user());

        $this->ticketService->addComment($ticket, $request->comment);

        return response()->json(['message' => 'Comment added successfully']);
    }

    /**
     * Search tickets
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user', 'assignedTo']);

        // Apply role-based filtering
        $this->applyRoleFilters($query, $user);

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

    /**
     * Helper to apply role-based filters to a ticket query
     */
    private function applyRoleFilters($query, $user)
    {
        if ($user->role === 'client' || $user->role === 'user') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'technician') {
            $query->where('assigned_to', $user->id);
        }
        // Admin sees everything
    }

    /**
     * Helper to check if a user can access a specific ticket (view/comment)
     */
    private function authorizeTicketAccess($ticket, $user)
    {
        if ($user->role === 'admin')
            return;

        if (($user->role === 'client' || $user->role === 'user') && $ticket->user_id === $user->id)
            return;

        if ($user->role === 'technician' && $ticket->assigned_to === $user->id)
            return;

        abort(403, 'Unauthorized access to this ticket.');
    }

    /**
     * Helper to check if a user can perform an action on a ticket (update)
     */
    private function authorizeTicketAction($ticket, $user)
    {
        if ($user->role === 'admin')
            return;

        // Clients can update their own tickets IF they are still open
        if (($user->role === 'client' || $user->role === 'user') && $ticket->user_id === $user->id) {
            return;
        }

        // Technicians can update tickets assigned to them
        if ($user->role === 'technician' && $ticket->assigned_to === $user->id) {
            return;
        }

        abort(403, 'Unauthorized action on this ticket.');
    }
}

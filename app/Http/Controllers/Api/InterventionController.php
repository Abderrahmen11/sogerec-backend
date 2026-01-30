<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\User;
use App\Notifications\InterventionAssignedNotification;
use App\Notifications\InterventionStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;


class InterventionController extends Controller
{
    /**
     * Get all interventions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Intervention::with(['ticket', 'user']);

        // Role-based filtering
        if ($user->role === 'client') {
            // Clients see interventions for their tickets
            $query->whereHas('ticket', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'technician') {
            // Technicians see their assigned interventions
            $query->where('user_id', $user->id);
        }
        // Admins see all

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $interventions = $query->orderBy('scheduled_at', 'desc')->get();

        return response()->json($interventions);
    }

    /**
     * Get a single intervention
     */
    public function show($id, Request $request)
    {
        $intervention = Intervention::with(['ticket.user', 'user'])->findOrFail($id);
        $user = $request->user();

        // Admin can see everything
        if ($user->role === 'admin') {
            return response()->json($intervention);
        }

        // Technician can see what's assigned to them
        if ($user->role === 'technician' && $intervention->user_id === $user->id) {
            return response()->json($intervention);
        }

        // Client can see interventions for their tickets
        if ($intervention->ticket && $intervention->ticket->user_id === $user->id) {
            return response()->json($intervention);
        }

        return response()->json(['message' => 'Unauthorized access to this intervention'], 403);
    }

    /**
     * Create a new intervention
     */
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'user_id' => 'sometimes|nullable|exists:users,id',
            'scheduled_at' => 'sometimes|nullable|date',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
        ]);

        // Automatic status logic
        $status = Intervention::STATUS_PENDING;
        if ($request->filled('user_id') && $request->filled('scheduled_at')) {
            $status = Intervention::STATUS_SCHEDULED;
        }

        $intervention = Intervention::create([
            'title' => $request->title ?? "Intervention for Ticket #{$request->ticket_id}",
            'ticket_id' => $request->ticket_id,
            'user_id' => $request->user_id,
            'scheduled_at' => $request->scheduled_at,
            'description' => $request->description,
            'status' => $status,
        ]);

        // Notify technician if scheduled
        if ($status === Intervention::STATUS_SCHEDULED && $intervention->user_id) {
            $technician = User::find($request->user_id);
            if ($technician) {
                $technician->notify(new InterventionAssignedNotification($intervention));
            }

            // Notify Client (Ticket Creator)
            $ticket = $intervention->ticket;
            if ($ticket && $ticket->user) {
                $ticket->user->notify(new \App\Notifications\InterventionScheduledNotification($intervention));
            }

            // Sync ticket status and assignment when an intervention is scheduled
            if ($intervention->ticket) {
                $intervention->ticket->update([
                    'status' => 'open',
                    'assigned_to_user_id' => $request->user_id  // Assign ticket to technician
                ]);
            }
        }

        return response()->json($intervention->load(['ticket', 'user']), 201);
    }

    /**
     * Update an intervention
     */
    public function update($id, Request $request)
    {
        $intervention = Intervention::findOrFail($id);

        $request->validate([
            'scheduled_at' => 'sometimes|date',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'description' => 'sometimes|string',
        ]);

        $intervention->update($request->only(['scheduled_at', 'status', 'description']));

        return response()->json($intervention->load(['ticket', 'user']));
    }

    /**
     * Delete an intervention
     */
    public function destroy($id)
    {
        $intervention = Intervention::findOrFail($id);
        $intervention->delete();

        return response()->json(['message' => 'Intervention deleted successfully']);
    }

    /**
     * Update intervention status
     */
    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:pending,scheduled,in_progress,completed,cancelled',
        ]);

        $intervention = Intervention::findOrFail($id);
        $user = $request->user();

        // Ownership check – Technicians can only update their own interventions
        if ($user->role === 'technician' && $intervention->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. This intervention is not assigned to you.'], 403);
        }

        $oldStatus = $intervention->status;
        $newStatus = $request->status;

        // Strict Transition Validation
        if ($newStatus === Intervention::STATUS_IN_PROGRESS && $oldStatus !== Intervention::STATUS_SCHEDULED) {
            return response()->json(['message' => 'Intervention must be scheduled before it can be started.'], 422);
        }

        if ($newStatus === Intervention::STATUS_COMPLETED && $oldStatus !== Intervention::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Intervention must be in progress before it can be completed.'], 422);
        }

        $intervention->update(['status' => $newStatus]);

        // Sync ticket status
        if ($intervention->ticket) {
            $ticketStatus = 'open';
            if ($newStatus === Intervention::STATUS_IN_PROGRESS) {
                $ticketStatus = 'in_progress';
            } elseif ($newStatus === Intervention::STATUS_COMPLETED) {
                $ticketStatus = 'closed';
            } elseif ($newStatus === Intervention::STATUS_PENDING) {
                $ticketStatus = 'open'; // Or whatever makes sense for a pending intervention
            }
            $intervention->ticket->update(['status' => $ticketStatus]);
        }

        // Notify admins if status changed
        if ($oldStatus !== $newStatus) {
            $admins = User::where('role', 'admin')->get();
            $message = "Intervention #{$intervention->id} is now " . str_replace('_', ' ', $newStatus);
            Notification::send($admins, new InterventionStatusUpdatedNotification($intervention, $message));
        }

        return response()->json($intervention->load(['ticket', 'user']));
    }

    /**
     * Submit intervention report
     */
    public function submitReport($id, Request $request)
    {
        $request->validate([
            'report' => 'required|string',
        ]);

        $intervention = Intervention::findOrFail($id);
        $user = $request->user();

        // Ownership check – Technicians can only submit reports for their own interventions
        if ($user->role === 'technician' && $intervention->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. This intervention is not assigned to you.'], 403);
        }

        // Mark as completed
        $intervention->update([
            'status' => Intervention::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Sync ticket status
        if ($intervention->ticket) {
            $intervention->ticket->update(['status' => 'closed']);
        }

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        $message = "Intervention #{$intervention->id} has been completed and report submitted";
        Notification::send($admins, new InterventionStatusUpdatedNotification($intervention, $message));

        return response()->json(['message' => 'Report submitted successfully', 'intervention' => $intervention]);


    }

    /**
     * Get planning/calendar view of interventions
     */
    public function planning(Request $request)
    {
        $query = Intervention::with(['ticket', 'user']);

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->where('scheduled_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('scheduled_at', '<=', $request->end_date);
        }

        $interventions = $query->orderBy('scheduled_at', 'asc')->get();

        return response()->json($interventions);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Events\InterventionAssigned;
use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\InterventionReport;
use App\Models\Planning;
use App\Models\User;
use App\Notifications\InterventionStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;


class InterventionController extends Controller
{
    /**
     * Get all interventions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Intervention::with(['ticket.user', 'user']);

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
            'user_id' => 'required|exists:users,id',
            'scheduled_at' => 'sometimes|nullable|date',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        // Validate technician role - MUST have technician role
        $technician = User::find($request->user_id);
        if (!$technician || $technician->role !== 'technician') {
            return response()->json([
                'message' => 'Invalid technician assignment. User must have technician role.',
                'error' => 'INVALID_TECHNICIAN'
            ], 422);
        }

        // Automatic status logic
        $status = Intervention::STATUS_PENDING;
        if ($request->filled('user_id') && $request->filled('scheduled_at')) {
            $status = Intervention::STATUS_SCHEDULED;
        }

        $intervention = null;
        $assignmentPersisted = false;

        try {
            $intervention = DB::transaction(function () use ($request, $status) {
                $intervention = Intervention::create([
                    'title' => $request->title ?? "Intervention for Ticket #{$request->ticket_id}",
                    'ticket_id' => $request->ticket_id,
                    'user_id' => $request->user_id,
                    'scheduled_at' => $request->scheduled_at ?? null,
                    'description' => $request->description ?? null,
                    'location' => $request->location ?? null,
                    'latitude' => $request->latitude ?? null,
                    'longitude' => $request->longitude ?? null,
                    'status' => $status,
                ]);

                $ticket = $intervention->ticket;
                if (!$ticket) {
                    throw new \RuntimeException('Ticket not found for intervention.');
                }

                // ALWAYS update ticket assignment when technician is provided
                $ticket->update([
                    'assigned_to' => $request->user_id,
                    'status' => 'assigned',
                ]);
                $ticket->refresh();
                if ((int) $ticket->assigned_to !== (int) $request->user_id) {
                    throw new \RuntimeException('Assignment verification failed. assigned_to does not match.');
                }

                // Create planning entry when scheduled
                if ($status === Intervention::STATUS_SCHEDULED && $request->filled('scheduled_at')) {
                    Planning::updateOrCreate(
                        ['intervention_id' => $intervention->id],
                        [
                            'technician_id' => $request->user_id,
                            'planned_date' => \Carbon\Carbon::parse($request->scheduled_at)->toDateString(),
                            'status' => 'scheduled',
                        ]
                    );
                }

                return $intervention;
            });
            $assignmentPersisted = true;

            // Log successful persistence to prevent duplicate event dispatch
            \Illuminate\Support\Facades\Log::info('Intervention assigned', [
                'intervention_id' => $intervention->id,
                'technician_id' => $intervention->user_id,
                'ticket_id' => $intervention->ticket_id,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('InterventionController@store - assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to assign technician: ' . $e->getMessage(),
                'error' => 'ASSIGNMENT_FAILED'
            ], 500);
        }

        $intervention->load(['ticket.assignedTo', 'user']);
        $ticket = $intervention->ticket;

        // Dispatch event ONLY ONCE after verified DB persistence (transaction committed)
        if ($assignmentPersisted && $intervention->user_id) {
            // Event dispatched ONCE, listeners will handle notifications
            event(new InterventionAssigned($intervention));
        }

        // Build response with explicit assignment confirmation
        $technicianName = $intervention->user?->name ?? $technician->name ?? null;

        return response()->json([
            'id' => $intervention->id,
            'ticket_id' => $intervention->ticket_id,
            'user_id' => $intervention->user_id,
            'assigned_to_user_id' => $ticket->assigned_to,
            'technician_name' => $technicianName,
            'success' => true,
            'assignment_confirmed' => true,
            'intervention' => $intervention,
        ], 201);
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
            'location' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $intervention->update($request->only(['scheduled_at', 'status', 'description', 'location', 'latitude', 'longitude']));

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

        if ($intervention->ticket) {
            $ticketStatus = match ($newStatus) {
                Intervention::STATUS_IN_PROGRESS => 'in_progress',
                Intervention::STATUS_COMPLETED => 'closed',
                Intervention::STATUS_CANCELLED => 'cancelled',
                Intervention::STATUS_SCHEDULED => 'assigned',
                default => $intervention->ticket->status,
            };
            $intervention->ticket->update(['status' => $ticketStatus]);
        }

        // Notify admins and client if status changed
        if ($oldStatus !== $newStatus) {
            // Notify admins
            $admins = User::where('role', 'admin')->get();
            $message = "Intervention #{$intervention->id} is now " . str_replace('_', ' ', $newStatus);
            Notification::send($admins, new InterventionStatusUpdatedNotification($intervention, $message));

            // Notify client (ticket creator)
            if ($intervention->ticket && $intervention->ticket->user) {
                $clientMessage = "Your intervention #{$intervention->id} status has been updated to " . str_replace('_', ' ', $newStatus);
                $intervention->ticket->user->notify(new InterventionStatusUpdatedNotification($intervention, $clientMessage));
            }
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
            'worked_hours' => 'sometimes|nullable|numeric|min:0',
        ]);

        $intervention = Intervention::findOrFail($id);
        $user = $request->user();

        if ($user->role === 'technician' && $intervention->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. This intervention is not assigned to you.'], 403);
        }

        $intervention = DB::transaction(function () use ($intervention, $request, $user) {
            InterventionReport::create([
                'intervention_id' => $intervention->id,
                'technician_id' => $intervention->user_id,
                'report' => $request->report,
                'content' => $request->report,
                'worked_hours' => $request->worked_hours,
                'status' => 'submitted',
            ]);

            $intervention->update([
                'status' => Intervention::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            if ($intervention->ticket) {
                $intervention->ticket->update(['status' => 'closed']);
            }

            return $intervention->fresh(['ticket', 'user', 'reports']);
        });

        $admins = User::where('role', 'admin')->get();
        $message = "Intervention #{$intervention->id} has been completed and report submitted";
        Notification::send($admins, new InterventionStatusUpdatedNotification($intervention, $message));

        if ($intervention->ticket && $intervention->ticket->user) {
            $clientMessage = "Your intervention #{$intervention->id} has been completed. The technician has submitted the report.";
            $intervention->ticket->user->notify(new InterventionStatusUpdatedNotification($intervention, $clientMessage));
        }

        return response()->json(['message' => 'Report submitted successfully', 'intervention' => $intervention]);
    }

    /**
     * Get planning/calendar view of interventions
     */
    public function planning(Request $request)
    {
        $user = $request->user();
        $query = Intervention::with(['ticket.user', 'user']);

        // Technicians see only their assigned interventions (user_id = technician_id)
        if ($user->role === 'technician') {
            $query->where('user_id', $user->id);
        }
        // Admins see all

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

    /**
     * Generate a report for completed interventions (Admin only)
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'intervention_id' => 'required|exists:interventions,id',
            'title' => 'sometimes|string|max:255',
            'summary' => 'required|string',
            'findings' => 'sometimes|string',
            'recommendations' => 'sometimes|string',
        ]);

        $intervention = Intervention::findOrFail($request->intervention_id);

        // Ensure report is only for completed interventions
        if ($intervention->status !== Intervention::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Report can only be generated for completed interventions.',
                'status' => $intervention->status
            ], 422);
        }

        $report = DB::transaction(function () use ($request, $intervention) {
            return InterventionReport::create([
                'intervention_id' => $intervention->id,
                'technician_id' => $intervention->user_id,
                'report' => $request->summary,
                'content' => json_encode([
                    'title' => $request->title ?? "Report for Intervention #{$intervention->id}",
                    'summary' => $request->summary,
                    'findings' => $request->findings ?? null,
                    'recommendations' => $request->recommendations ?? null,
                ]),
                'status' => 'submitted',
            ]);
        });

        return response()->json([
            'message' => 'Report generated successfully',
            'report' => $report
        ], 201);
    }

    /**
     * Get all reports (Admin only)
     */
    public function getReports(Request $request)
    {
        $reports = InterventionReport::with(['intervention', 'intervention.ticket'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($reports);
    }
}

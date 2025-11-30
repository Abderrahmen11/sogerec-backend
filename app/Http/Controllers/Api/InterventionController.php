<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    /**
     * Get all interventions
     */
    public function index(Request $request)
    {
        $query = Intervention::with(['ticket', 'user']);

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
    public function show($id)
    {
        $intervention = Intervention::with(['ticket', 'user'])->findOrFail($id);
        return response()->json($intervention);
    }

    /**
     * Create a new intervention
     */
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'user_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date',
            'description' => 'sometimes|string',
        ]);

        $intervention = Intervention::create([
            'ticket_id' => $request->ticket_id,
            'user_id' => $request->user_id,
            'scheduled_at' => $request->scheduled_at,
            'description' => $request->description,
            'status' => 'scheduled',
        ]);

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
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $intervention = Intervention::findOrFail($id);
        $intervention->update(['status' => $request->status]);

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

        // For now, just mark as completed. You can add a report field to the table later
        $intervention->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

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
            $query->where('scheduled_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('scheduled_date', '<=', $request->end_date);
        }

        $interventions = $query->orderBy('scheduled_date', 'asc')->get();

        return response()->json($interventions);
    }
}

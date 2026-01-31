<?php

namespace App\Http\Controllers\Api;

use App\Models\Planning;
use App\Models\Intervention;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PlanningController extends Controller
{
    /**
     * Get planning list (technician sees only their own, admin sees all)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Planning::with(['intervention', 'intervention.ticket', 'technician']);

        // Technicians see only their own planning
        if ($user->role === 'technician') {
            $query->where('technician_id', $user->id);
        }
        // Admins see all

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->where('planned_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('planned_date', '<=', $request->end_date);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $planning = $query->orderBy('planned_date', 'asc')->get();

        return response()->json($planning);
    }

    /**
     * Get a specific planning entry
     */
    public function show($id, Request $request)
    {
        $planning = Planning::with(['intervention', 'intervention.ticket', 'technician'])
            ->findOrFail($id);

        $user = $request->user();

        // Admin can see everything
        if ($user->role === 'admin') {
            return response()->json($planning);
        }

        // Technician can see their own planning
        if ($user->role === 'technician' && $planning->technician_id === $user->id) {
            return response()->json($planning);
        }

        return response()->json(['message' => 'Unauthorized access to this planning'], 403);
    }

    /**
     * Get planning for current technician (shortcut)
     */
    public function myPlanning(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'technician') {
            return response()->json(['message' => 'Only technicians can view their planning'], 403);
        }

        $planning = Planning::with(['intervention', 'intervention.ticket', 'technician'])
            ->where('technician_id', $user->id)
            ->orderBy('planned_date', 'asc')
            ->get();

        return response()->json($planning);
    }
}

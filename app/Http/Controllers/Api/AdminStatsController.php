<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function index()
    {
        $usersCount = User::where('role', 'client')->count();
        $techniciansCount = User::where('role', 'technician')->count();
        $openTicketsCount = Ticket::where('status', 'open')->count();

        // Requests by day (last 7 days)
        $requestsByDay = Ticket::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Ensure all 7 days are present
        $requestsData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $requestsData[] = $requestsByDay[$date] ?? 0;
        }

        // Technician performance (completed interventions)
        $techPerformance = User::where('role', 'technician')
            ->withCount([
                'interventions' => function ($query) {
                    $query->where('status', 'completed');
                }
            ])
            ->orderByDesc('interventions_count')
            ->limit(5)
            ->get(['name', 'interventions_count']);

        // Requests by category
        $categories = Ticket::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        // Recent activity (mix of tickets and interventions)
        $recentTickets = Ticket::with('user')->latest()->limit(3)->get()->map(function ($t) {
            return "New client request submitted: {$t->title} by {$t->user->name}";
        });

        $recentInterventions = Intervention::with('user')->where('status', 'completed')->latest()->limit(2)->get()->map(function ($i) {
            return "Technician {$i->user->name} completed intervention #{$i->id}";
        });

        return response()->json([
            'stats' => [
                'usersCount' => $usersCount,
                'techniciansCount' => $techniciansCount,
                'openTicketsCount' => $openTicketsCount,
            ],
            'charts' => [
                'requests' => [
                    'labels' => collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('D'))->toArray(),
                    'data' => $requestsData
                ],
                'technicians' => [
                    'labels' => $techPerformance->pluck('name'),
                    'data' => $techPerformance->pluck('interventions_count')
                ],
                'categories' => [
                    'labels' => $categories->pluck('category'),
                    'data' => $categories->pluck('count')
                ]
            ],
            'notifications' => $recentTickets->concat($recentInterventions)->take(5)
        ]);
    }
}

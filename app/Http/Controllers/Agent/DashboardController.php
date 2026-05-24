<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $stats = [
            'assigned_tickets'  => Ticket::where('assigned_to', $userId)->count(),
            'open_assigned'     => Ticket::where('assigned_to', $userId)->whereIn('status', ['open', 'assigned'])->count(),
            'in_progress_count' => Ticket::where('assigned_to', $userId)->where('status', 'in_progress')->count(),
            'resolved_by_me'    => Ticket::where('assigned_to', $userId)->where('status', 'resolved')->count(),
            'team_resolved'     => Ticket::where('status', 'resolved')->count(),
            'pending_approval'  => Ticket::where('assigned_to', $userId)->whereIn('status', ['pending_user_response', 'pending_admin_approval'])->count(),
            'on_hold'           => Ticket::where('assigned_to', $userId)->where('status', 'pending')->count(),
            'overdue'           => Ticket::where('assigned_to', $userId)->whereIn('status', ['open', 'assigned', 'in_progress'])->where('created_at', '<', now()->subDays(3))->count(),
            'avg_response_time' => 'N/A',
            'my_rating'         => Auth::user()->rating ?? 0,
            'closed_count'      => Ticket::where('assigned_to', $userId)->where('status', 'closed')->count(),
        ];

        $myTickets      = Ticket::where('assigned_to', $userId)->with('user')->orderBy('created_at', 'desc')->take(10)->get();
        $trends         = $this->getMyTicketTrends($userId);
        $statusCounts   = $this->getMyStatusDistribution($userId);

        return view('agent.dashboard', compact('stats', 'myTickets', 'trends', 'statusCounts'));
    }

    private function getMyTicketTrends($userId, $days = 7)
    {
        $dates  = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $dates[]  = now()->subDays($i)->format('M d');
            $counts[] = Ticket::where('assigned_to', $userId)->whereDate('created_at', $date)->count();
        }

        return ['dates' => $dates, 'counts' => $counts];
    }

    private function getMyStatusDistribution($userId)
    {
        return [
            'labels' => ['Open', 'In Progress', 'Resolved', 'Closed'],
            'counts' => [
                Ticket::where('assigned_to', $userId)->whereIn('status', ['open', 'assigned'])->count(),
                Ticket::where('assigned_to', $userId)->where('status', 'in_progress')->count(),
                Ticket::where('assigned_to', $userId)->where('status', 'resolved')->count(),
                Ticket::where('assigned_to', $userId)->where('status', 'closed')->count(),
            ],
        ];
    }

}
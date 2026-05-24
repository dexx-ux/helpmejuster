@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Agent Dashboard
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Welcome Banner for New Agents -->
        @if(auth()->user()->approved_at && auth()->user()->approved_at->diffInDays(now()) <= 7)
        <div class="bg-gradient-to-r from-green-500 to-blue-600 text-white rounded-xl shadow-lg p-6 mb-6 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">
                        Welcome to the Team!
                    </h3>
                    <p class="text-green-100">
                        Your agent account is <strong>ACTIVE</strong>! Agent ID: <span class="font-mono font-bold text-lg">{{ auth()->user()->employee_id }}</span>
                    </p>
                    @if(auth()->user()->department)
                    <p class="text-green-100 text-sm mt-1">
                        Department: {{ auth()->user()->department }}
                    </p>
                    @endif
                </div>
                <div class="text-4xl">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        @endif

        <!-- Statistics Cards - Row 1 (Agent Focused Metrics with Links) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
            <!-- Assigned to Me Card -->
            <a href="{{ route('agent.tickets.assigned') }}" class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Assigned to Me</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['assigned_tickets']) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-person-badge text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Open / In Progress Card -->
            <a href="{{ route('agent.tickets.index') }}" class="group relative overflow-hidden bg-gradient-to-br from-yellow-500 to-yellow-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Open / In Progress</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['open_assigned'] + $stats['in_progress_count']) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-clock-history text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Resolved by Me Card -->
            <a href="{{ route('agent.tickets.resolved') }}" class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-green-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Resolved by Me</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['resolved_by_me']) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- My Team's Performance Card -->
            <a href="{{ route('agent.tickets.index') }}" class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Team Resolved</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['team_resolved'] ?? 0) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Statistics Cards - Row 2 (Additional Agent Metrics with Links) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
            <!-- Pending Approval Card -->
            <a href="{{ route('agent.tickets.index') }}" class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Pending Approval</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['pending_approval'] ?? 0) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- On Hold Card -->
            <a href="{{ route('agent.tickets.index') }}" class="group relative overflow-hidden bg-gradient-to-br from-gray-500 to-gray-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">On Hold</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['on_hold'] ?? 0) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-pause-circle text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Overdue Tickets Card -->
            <a href="{{ route('agent.tickets.index') }}" class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-red-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Overdue</p>
                            <p class="text-lg md:text-2xl font-bold text-white mt-1">{{ number_format($stats['overdue'] ?? 0) }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-exclamation-triangle text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Average Response Time Card -->
            <div class="group relative overflow-hidden bg-gradient-to-br from-teal-500 to-teal-700 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-not-allowed">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="absolute -right-4 -top-4 w-14 h-14 bg-white/10 rounded-full"></div>
                <div class="relative p-3 md:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-teal-100 text-[10px] md:text-xs font-medium uppercase tracking-wide">Avg Response Time</p>
                            <p class="text-sm md:text-lg font-bold text-white mt-1">{{ $stats['avg_response_time'] ?? 'N/A' }}</p>
                        </div>
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-stopwatch text-white text-base md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Recent Tickets Table --}}
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-clock-history text-blue-500 text-xl"></i>
                My Recent Tickets
            </h3>
            <a href="{{ route('agent.tickets.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 transition-colors">
                View All →
            </a>
        </div>
        @if($myTickets->count() > 0)
        <div class="overflow-x-auto" style="max-height: 400px;">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0 z-10">
                    <tr class="text-left">
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($myTickets as $ticket)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-mono font-medium text-gray-900 dark:text-white">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('agent.tickets.show', $ticket->id) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $ticket->subject }}
                            </a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $ticket->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full font-medium
                                @if($ticket->status == 'open') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                @elseif($ticket->status == 'in_progress') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif($ticket->status == 'resolved') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $ticket->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="h-40 flex items-center justify-center text-gray-500 dark:text-gray-400">
            <div class="text-center">
                <i class="bi bi-inbox text-3xl mb-2 block"></i>
                <p class="text-sm">No tickets assigned yet.</p>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Ticket Trends + Status Distribution --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
            <i class="bi bi-graph-up text-blue-500 text-lg"></i>
            My Ticket Trends (7 days)
        </h3>
        <div style="height: 280px;">
            <canvas id="agentTrendsChart"></canvas>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
            <i class="bi bi-pie-chart text-purple-500 text-lg"></i>
            My Status Distribution
        </h3>
        <div style="height: 250px;">
            <canvas id="agentStatusChart"></canvas>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-center">
            <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['open_assigned']) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Open</p>
            </div>
            <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['in_progress_count']) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">In Progress</p>
            </div>
            <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($stats['resolved_by_me']) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Resolved</p>
            </div>
            <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ number_format($stats['closed_count']) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Closed</p>
            </div>
        </div>
    </div>
</div>

</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const trendsData = @json($trends ?? []);
        if (document.getElementById('agentTrendsChart') && trendsData.dates) {
            new Chart(document.getElementById('agentTrendsChart'), {
                type: 'line',
                data: {
                    labels: trendsData.dates,
                    datasets: [{
                        label: 'Tickets Assigned',
                        data: trendsData.counts,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
            });
        }

        const statusData = @json($statusCounts ?? []);
        if (document.getElementById('agentStatusChart') && statusData.labels) {
            new Chart(document.getElementById('agentStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.counts,
                        backgroundColor: ['rgb(234, 179, 8)', 'rgb(59, 130, 246)', 'rgb(34, 197, 94)', 'rgb(107, 114, 128)'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
    });
</script>
@endpush
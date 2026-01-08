<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketsExport;

class ReportController extends Controller
{
    /**
     * Get paginated report data
     */
    public function index(Request $request)
    {
        $query = $this->buildQuery($request);

        $tickets = $query->with(['requestor', 'department', 'category', 'assignedTo', 'priority'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($tickets);
    }

    /**
     * Get analytics data for dashboard
     */
    public function analytics(Request $request)
    {
        $query = $this->buildQuery($request);
        
        // Clone query for different aggregations to avoid residual bindings
        
        // 1. Tickets by Department
        $byDepartment = DB::table('tickets')
            ->join('departments', 'tickets.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.name')
            ->when($request->start_date, function($q) use ($request) {
                return $q->whereDate('tickets.created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function($q) use ($request) {
                return $q->whereDate('tickets.created_at', '<=', $request->end_date);
            })
            ->when($request->status, function($q) use ($request) {
                 return $q->where('tickets.status', $request->status);
            })
             ->orderByDesc('count')
            ->get();

        // 2. Top Requestors
        $topRequestors = DB::table('tickets')
            ->join('users', 'tickets.requestor_id', '=', 'users.id')
            ->select(DB::raw('COALESCE(users.employee_name, users.username) as name'), DB::raw('count(*) as count'))
            ->groupBy('name')
            ->when($request->start_date, function($q) use ($request) {
                return $q->whereDate('tickets.created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function($q) use ($request) {
                 return $q->whereDate('tickets.created_at', '<=', $request->end_date);
            })
             ->when($request->department_id, function($q) use ($request) {
                return $q->where('tickets.department_id', $request->department_id);
            })
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 3. Tickets by Category
        $byCategory = DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.name')
             ->when($request->start_date, function($q) use ($request) {
                return $q->whereDate('tickets.created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function($q) use ($request) {
                 return $q->whereDate('tickets.created_at', '<=', $request->end_date);
            })
            ->orderByDesc('count')
            ->get();

        // 4. Volume Trend (Last 7 days or selected range)
        $trendStart = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subDays(6);
        $trendEnd = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();
        
        $trend = Ticket::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', $trendStart)
            ->whereDate('created_at', '<=', $trendEnd)
             ->when($request->department_id, function($q) use ($request) {
                return $q->where('department_id', $request->department_id);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 5. Time & Motion Metrics
        // Avg Response Time: created_at -> assigned_at (or first activity)
        // Note: simplified calculation
        $avgResponse = Ticket::whereNotNull('assigned_at')
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, assigned_at)) as avg_minutes'))
            ->value('avg_minutes');

        // Avg Resolution Time: assigned_at -> resolved_at
        $avgResolution = Ticket::whereNotNull('resolved_at')
            ->whereNotNull('assigned_at')
             ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, resolved_at)) as avg_minutes'))
            ->value('avg_minutes');
            
        // Avg Turnaround Time: created_at -> resolved_at (Total)
        $avgTurnaround = Ticket::whereNotNull('resolved_at')
             ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes'))
            ->value('avg_minutes');

        // Total Counts
        $totalTickets = Ticket::when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->count();
            
        $openTickets = Ticket::whereIn('status', ['new', 'assigned', 'in_progress', 'reopened'])
             ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->count();
            
        $closedTickets = Ticket::whereIn('status', ['resolved', 'closed'])
             ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
             ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->count();


        return response()->json([
            'by_department' => $byDepartment,
            'top_requestors' => $topRequestors,
            'by_category' => $byCategory,
            'trend' => $trend,
            'metrics' => [
                'avg_response' => round($avgResponse / 60, 1), // Hours
                'avg_resolution' => round($avgResolution / 60, 1), // Hours
                'avg_turnaround' => round($avgTurnaround / 60, 1), // Hours
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'closed_tickets' => $closedTickets,
            ]
        ]);
    }

    /**
     * Export report
     */
    public function export(Request $request)
    {
        $fileName = 'tickets_report_' . date('Ymd_His');

        if ($request->type === 'pdf') {
            $query = $this->buildQuery($request);
            $tickets = $query->with(['requestor', 'department', 'category', 'assignedTo', 'priority'])
                ->orderBy('created_at', 'desc')
                ->get();

            $pdf = Pdf::loadView('reports.tickets', ['tickets' => $tickets]);
            return $pdf->download($fileName . '.pdf');
        } 
        
        if ($request->type === 'excel') {
            return Excel::download(new TicketsExport($request), $fileName . '.xlsx');
        }

        if ($request->type === 'csv') {
            return Excel::download(new TicketsExport($request), $fileName . '.csv');
        }

        return response()->json(['error' => 'Invalid export type'], 400);
    }

    private function buildQuery(Request $request)
    {
        $query = Ticket::query();

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return $query;
    }
}

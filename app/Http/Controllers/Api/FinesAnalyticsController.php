<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class FinesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', '30');

        if ($range === 'custom') {
            $from = $request->get('from') ? Carbon::parse($request->get('from')) : now()->subDays(29);
            $to   = $request->get('to') ? Carbon::parse($request->get('to')) : now();
        } else {
            $days = in_array($range, ['7','30','90']) ? (int)$range : 30;
            $to = Carbon::today();
            $from = $to->copy()->subDays($days - 1);
        }

        $from_date = $from->startOfDay();
        $to_date = $to->endOfDay();

        // CSV export for the main range
        if ($request->get('export') === 'csv') {
            return $this->exportCsvRange($from_date, $to_date);
        }

        // Summary cards
        $summary = [
            'total_count' => TrafficFine::whereBetween('created_at', [$from_date, $to_date])->count(),
            'total_amount' => (float) TrafficFine::whereBetween('created_at', [$from_date, $to_date])
                ->sum(DB::raw('COALESCE(ticket_amount,0) + COALESCE(late_fee,0)')),
            'total_unpaid' => TrafficFine::whereBetween('created_at', [$from_date, $to_date])
                ->whereRaw("UPPER(status) != 'PAID'")->count(),
            'total_paid' => TrafficFine::whereBetween('created_at', [$from_date, $to_date])
                ->whereRaw("UPPER(status) = 'PAID'")->count(),
            'from' => $from_date->toDateString(),
            'to' => $to_date->toDateString(),
        ];

        // Timeseries
        $timeseriesRaw = TrafficFine::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as amount')
        )
            ->whereBetween('created_at', [$from_date, $to_date])
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $row = $timeseriesRaw->get($key);
            $dates[] = [
                'date' => $key,
                'count' => $row ? (int)$row->count : 0,
                'amount' => $row ? (float)$row->amount : 0.0,
            ];
        }

        // Top violations
        $topViolations = DB::table('traffic_fine_violations')
            ->join('traffic_fines','traffic_fine_violations.traffic_fine_id','traffic_fines.id')
            ->select('traffic_fine_violations.violation_name', DB::raw('COUNT(*) as occurrences'))
            ->whereBetween('traffic_fines.created_at', [$from_date, $to_date])
            ->groupBy('violation_name')
            ->orderByDesc('occurrences')
            ->limit(10)->get();

        // Top vehicles
        $topVehicles = TrafficFine::select('plate_number', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as total_amount'))
            ->whereBetween('created_at', [$from_date, $to_date])
            ->whereNotNull('plate_number')->where('plate_number','!=','')
            ->groupBy('plate_number')->orderByDesc('count')->limit(10)->get();

        return response()->json([
            'summary' => $summary,
            'timeseries' => $dates,
            'top_violations' => $topViolations,
            'top_vehicles' => $topVehicles,
            'recent' => TrafficFine::with('violations')->whereBetween('created_at', [$from_date, $to_date])->orderByDesc('created_at')->limit(10)->get()
        ]);
    }

    // ... inside FinesAnalyticsController class

    /**
     * Drill-down: Fines for a specific day with Search, Status filter, and Meta Counts.
     */
    public function byDay(Request $request)
    {
        $date = Carbon::parse($request->get('date'))->toDateString();
        $search = $request->get('search');
        $status = $request->get('status');

        // 1. Initialize Base Query for this date
        $query = TrafficFine::whereDate('created_at', $date);

        // 2. Apply Search Filter (if provided)
        // We apply this BEFORE counts so the counts reflect the search results
        if ($search) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        // 3. Calculate Meta Counts (cloning the query to avoid cross-pollution)
        $meta_counts = [
            'all'    => (clone $query)->count(),
            'paid'   => (clone $query)->whereRaw("UPPER(status) = 'PAID'")->count(),
            'unpaid' => (clone $query)->whereRaw("UPPER(status) != 'PAID'")->count(),
        ];

        // 4. Apply Status Filter to the final result set
        if ($status) {
            if (strtolower($status) === 'unpaid') {
                $query->whereRaw("UPPER(status) != 'PAID'");
            } else {
                $query->whereRaw("UPPER(status) = 'PAID'");
            }
        }

        // 5. Paginate and Return
        $results = $query->with(['violations', 'fineable'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'results'     => $results,
            'meta_counts' => $meta_counts
        ]);
    }

    /**
     * Drill-down: Fines by Violation Type with Search, Status filter, and Meta Counts.
     */
    public function byViolation(Request $request)
    {
        $name   = $request->get('violation_name');
        $from   = Carbon::parse($request->get('from'))->startOfDay();
        $to     = Carbon::parse($request->get('to'))->endOfDay();
        $search = $request->get('search');
        $status = $request->get('status');

        // 1. Initialize Base Query for this violation and range
        $query = TrafficFine::whereHas('violations', function($q) use ($name) {
            $q->where('violation_name', $name);
        })
            ->whereBetween('created_at', [$from, $to]);

        // 2. Apply Search Filter
        if ($search) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        // 3. Calculate Meta Counts
        $meta_counts = [
            'all'    => (clone $query)->count(),
            'paid'   => (clone $query)->whereRaw("UPPER(status) = 'PAID'")->count(),
            'unpaid' => (clone $query)->whereRaw("UPPER(status) != 'PAID'")->count(),
        ];

        // 4. Apply Status Filter
        if ($status) {
            if (strtolower($status) === 'unpaid') {
                $query->whereRaw("UPPER(status) != 'PAID'");
            } else {
                $query->whereRaw("UPPER(status) = 'PAID'");
            }
        }

        // 5. Paginate and Return
        $results = $query->with(['violations', 'fineable'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'results'     => $results,
            'meta_counts' => $meta_counts
        ]);
    }

    /**
     * Export CSV for a specific day, respecting search and status filters.
     */
    public function exportDay(Request $request)
    {
        $date = Carbon::parse($request->get('date'))->toDateString();
        $search = $request->get('search');
        $status = $request->get('status');

        $query = TrafficFine::whereDate('created_at', $date);

        if ($search) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        if ($status) {
            if (strtolower($status) === 'unpaid') $query->whereRaw("UPPER(status) != 'PAID'");
            else $query->whereRaw("UPPER(status) = 'PAID'");
        }

        return $this->generateCsvStream("fines_export_$date.csv", $query);
    }

    /**
     * Export CSV for a specific violation, respecting range, search, and status filters.
     */
    public function exportViolationCsv(Request $request)
    {
        $name = $request->get('violation_name');
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();
        $search = $request->get('search');
        $status = $request->get('status');

        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $name))
            ->whereBetween('created_at', [$from, $to]);

        if ($search) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        if ($status) {
            if (strtolower($status) === 'unpaid') $query->whereRaw("UPPER(status) != 'PAID'");
            else $query->whereRaw("UPPER(status) = 'PAID'");
        }

        $filename = "violation_" . str_replace(' ', '_', $name) . "_export.csv";
        return $this->generateCsvStream($filename, $query);
    }

    protected function generateCsvStream($filename, $query): StreamedResponse
    {
        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Plate', 'Ticket', 'Amount', 'Status', 'Date']);
            foreach ($query->cursor() as $f) {
                fputcsv($handle, [$f->id, $f->plate_number, $f->ticket_number, $f->ticket_amount, $f->status, $f->created_at]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}

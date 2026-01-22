<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if ($request->get('export') === 'csv') {
            return $this->exportCsvRange($from_date, $to_date);
        }

        $today = now()->toDateString();
        $threeDays = now()->addDays(3)->toDateString();

        // Base Query for the selected period
        $baseQuery = TrafficFine::whereBetween('created_at', [$from_date, $to_date]);

        $summary = [
            // 1. Penalized: Already late (late_fee > 0)
            'penalized_count' => (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")
                ->where('late_fee', '>', 0)->count(),

            // 2. Expiring: No late fee yet, but due within 72 hours
            'expiring_soon_count' => (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")
                ->where('late_fee', 0)
                ->whereBetween('pay_by', [$today, $threeDays])->count(),

            // 3. Financial Breakdown
            'total_penalties_amount' => (float) (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")
                ->sum('late_fee'),
            'total_expiring_tickets_amount' => (float) (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")
                ->where('late_fee', 0)
                ->whereBetween('pay_by', [$today, $threeDays])
                ->sum('ticket_amount'),

            'total_paid' => (clone $baseQuery)->whereRaw("UPPER(status) = 'PAID'")->count(),
            'total_count' => (clone $baseQuery)->count(),
            'from' => $from_date->toDateString(),
            'to' => $to_date->toDateString(),
        ];

        // Timeseries
        $timeseriesRaw = TrafficFine::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$from_date, $to_date])
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $dates[] = ['date' => $key, 'count' => $timeseriesRaw->has($key) ? (int)$timeseriesRaw->get($key)->count : 0];
        }

        // 4. Top Violations (ONLY NON-SETTLED)
        $topViolations = DB::table('traffic_fine_violations')
            ->join('traffic_fines', 'traffic_fine_violations.traffic_fine_id', 'traffic_fines.id')
            ->select('traffic_fine_violations.violation_name', DB::raw('COUNT(*) as occurrences'))
            ->whereBetween('traffic_fines.created_at', [$from_date, $to_date])
            ->whereRaw("UPPER(traffic_fines.status) != 'PAID'") // Filter unsettled
            ->groupBy('violation_name')->orderByDesc('occurrences')->limit(10)->get();

        return response()->json([
            'summary' => $summary,
            'timeseries' => $dates,
            'top_violations' => $topViolations
        ]);
    }

    public function byDay(Request $request) {
        $query = TrafficFine::whereDate('created_at', Carbon::parse($request->get('date'))->toDateString());
        return $this->handleDrillDown($query, $request);
    }

    public function byViolation(Request $request) {
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();
        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $request->get('violation_name')))
            ->whereBetween('created_at', [$from, $to]);
        return $this->handleDrillDown($query, $request);
    }

    private function handleDrillDown($query, $request) {
        if ($search = $request->get('search')) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        $meta_counts = [
            'all'    => (clone $query)->count(),
            'paid'   => (clone $query)->whereRaw("UPPER(status) = 'PAID'")->count(),
            'at_risk'=> (clone $query)->whereRaw("UPPER(status) != 'PAID'")
                ->where(fn($q) => $q->where('late_fee', '>', 0)->orWhere('pay_by', '<=', now()->addDays(3)))->count(),
        ];

        // Default to risky fines
        $status = $request->get('status', 'at_risk');

        if ($status === 'at_risk') {
            $query->whereRaw("UPPER(status) != 'PAID'")
                ->where(fn($q) => $q->where('late_fee', '>', 0)->orWhere('pay_by', '<=', now()->addDays(3)));
        } elseif ($status === 'paid') {
            $query->whereRaw("UPPER(status) = 'PAID'");
        }

        return response()->json([
            'results' => $query->with(['violations'])->orderBy('pay_by', 'asc')->paginate(20),
            'meta_counts' => $meta_counts
        ]);
    }

    private function exportCsvRange($from, $to) {
        $headers = [ 'Content-type' => 'text/csv', 'Content-Disposition' => "attachment; filename=fines_risk_report.csv" ];
        return response()->stream(function() use ($from, $to) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Plate', 'Ticket', 'Status', 'Late Fee', 'Ticket Amount', 'Pay By']);
            TrafficFine::whereBetween('created_at', [$from, $to])->chunk(200, function($fines) use ($file) {
                foreach($fines as $f) fputcsv($file, [$f->created_at, $f->plate_number, $f->ticket_number, $f->status, $f->late_fee, $f->ticket_amount, $f->pay_by]);
            });
            fclose($file);
        }, 200, $headers);
    }
}

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

        if ($request->get('export') === 'csv') {
            return $this->exportCsvRange($from_date, $to_date);
        }

        // Summary calculations
        $baseQuery = TrafficFine::whereBetween('created_at', [$from_date, $to_date]);

        // Critical: Calculate Risk (Unpaid fines near pay_by date)
        $today = now();
        $threeDaysFromNow = now()->addDays(3);

        $summary = [
            'total_count' => (clone $baseQuery)->count(),
            'total_amount' => (float) (clone $baseQuery)->sum(DB::raw('COALESCE(ticket_amount,0) + COALESCE(late_fee,0)')),
            'total_unpaid' => (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")->count(),
            'total_paid' => (clone $baseQuery)->whereRaw("UPPER(status) = 'PAID'")->count(),
            // Late Fee specific metrics
            'already_penalized' => (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")->where('late_fee', '>', 0)->count(),
            'at_risk_count' => (clone $baseQuery)->whereRaw("UPPER(status) != 'PAID'")
                ->where('late_fee', 0)
                ->whereBetween('pay_by', [$today->toDateString(), $threeDaysFromNow->toDateString()])
                ->count(),
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
            ->groupBy('violation_name')->orderByDesc('occurrences')->limit(10)->get();

        return response()->json([
            'summary' => $summary,
            'timeseries' => $dates,
            'top_violations' => $topViolations,
            'recent' => TrafficFine::with('violations')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->orderByDesc('created_at')->limit(10)->get()
        ]);
    }

    public function byDay(Request $request) {
        $date = Carbon::parse($request->get('date'))->toDateString();
        $query = TrafficFine::whereDate('created_at', $date);
        return $this->handleDrillDown($query, $request);
    }

    public function byViolation(Request $request) {
        $name = $request->get('violation_name');
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();
        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $name))
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
            'unpaid' => (clone $query)->whereRaw("UPPER(status) != 'PAID'")->count(),
            'at_risk'=> (clone $query)->whereRaw("UPPER(status) != 'PAID'")
                ->where(fn($q) => $q->where('late_fee', '>', 0)->orWhere('pay_by', '<=', now()->addDays(3)))->count(),
        ];

        if ($request->get('status') === 'at_risk') {
            $query->whereRaw("UPPER(status) != 'PAID'")
                ->where(fn($q) => $q->where('late_fee', '>', 0)->orWhere('pay_by', '<=', now()->addDays(3)));
        } elseif ($status = $request->get('status')) {
            $query->whereRaw(strtolower($status) === 'unpaid' ? "UPPER(status) != 'PAID'" : "UPPER(status) = 'PAID'");
        }

        return response()->json([
            'results' => $query->with(['violations'])->orderBy('pay_by', 'asc')->paginate(20),
            'meta_counts' => $meta_counts
        ]);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TrafficFine;
use App\Models\TrafficFineViolation;
use Carbon\Carbon;

class FinesAnalyticsController extends Controller
{
    /**
     * GET /api/portal/fines/analytics
     * Returns aggregates for the dashboard.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $from30 = $today->copy()->subDays(29); // inclusive last 30 days
        $monthsBack = $today->copy()->subMonths(11); // last 12 months

        // 1) Summary cards
        $totalThisMonth = TrafficFine::whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->count();

        $totalThisMonthAmount = TrafficFine::whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->sum(DB::raw('COALESCE(ticket_amount,0) + COALESCE(late_fee,0)'));

        $totalUnpaid = TrafficFine::whereRaw("UPPER(status) != 'PAID'")->count();

        $totalPaid = TrafficFine::whereRaw("UPPER(status) = 'PAID'")->count();

        // 2) Timeseries last 30 days (date => count)
        $timeseries = TrafficFine::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(COALESCE(ticket_amount,0) + COALESCE(late_fee,0)) as amount')
        )
            ->whereDate('created_at', '>=', $from30->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date' => $r->date,
                'count' => (int)$r->count,
                'amount' => (float)$r->amount,
            ]);

        // Fill missing days for last 30 days (so charts are continuous)
        $dates = [];
        for ($d = $from30->copy(); $d->lte($today); $d->addDay()) {
            $dates[$d->toDateString()] = ['date' => $d->toDateString(), 'count' => 0, 'amount' => 0.0];
        }
        foreach ($timeseries as $t) {
            $dates[$t['date']] = $t;
        }
        $timeseries = array_values($dates);

        // 3) Top violations (by occurrences and amount)
        $topViolations = DB::table('traffic_fine_violations')
            ->select('violation_name', DB::raw('COUNT(*) as occurrences'), DB::raw('SUM(COALESCE(fine_amount,0)) as total_amount'))
            ->groupBy('violation_name')
            ->orderByDesc('occurrences')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'violation_name' => $r->violation_name,
                'occurrences' => (int)$r->occurrences,
                'total_amount' => (float)$r->total_amount,
            ]);

        // 4) Top vehicles (by fines count)
        $topVehicles = TrafficFine::select('plate_number', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as total_amount'))
            ->whereNotNull('plate_number')
            ->where('plate_number', '!=', '')
            ->groupBy('plate_number')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'plate_number' => $r->plate_number,
                'count' => (int)$r->count,
                'total_amount' => (float)$r->total_amount,
            ]);

        // 5) Top trailers (by fines) — fineable_type filter
        $topTrailers = TrafficFine::select('plate_number', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as total_amount'))
            ->where('fineable_type', 'App\\Models\\Trailer')
            ->whereNotNull('plate_number')
            ->where('plate_number', '!=', '')
            ->groupBy('plate_number')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'plate_number' => $r->plate_number,
                'count' => (int)$r->count,
                'total_amount' => (float)$r->total_amount,
            ]);

        // 6) Monthly totals last 12 months
        $monthly = TrafficFine::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as amount')
        )
            ->whereDate('created_at', '>=', $monthsBack->toDateString())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // ensure every month exists (last 12 months)
        $months = [];
        for ($m = $monthsBack->copy(); $m->lte($today); $m->addMonth()) {
            $key = $m->format('Y-m');
            if (isset($monthly[$key])) {
                $months[] = [
                    'month' => $key,
                    'count' => (int)$monthly[$key]->count,
                    'amount' => (float)$monthly[$key]->amount,
                ];
            } else {
                $months[] = ['month' => $key, 'count' => 0, 'amount' => 0.0];
            }
        }

        // 7) Recent fines (latest 10)
        $recent = TrafficFine::with('violations','fineable')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'plate_number' => $f->plate_number,
                'fineable_type' => $f->fineable_type,
                'ticket_number' => $f->ticket_number,
                'ticket_amount' => (float)$f->ticket_amount,
                'status' => $f->status,
                'issued_at' => $f->issued_at?->toDateString(),
                'pay_by' => $f->pay_by?->toDateString(),
                'created_at' => $f->created_at->toDateTimeString(),
                'violations' => $f->violations->map(fn($v) => ['name'=>$v->violation_name,'amount'=>(float)$v->fine_amount])->values(),
            ]);

        return response()->json([
            'summary' => [
                'total_this_month' => (int)$totalThisMonth,
                'total_this_month_amount' => (float)$totalThisMonthAmount,
                'total_unpaid' => (int)$totalUnpaid,
                'total_paid' => (int)$totalPaid,
            ],
            'timeseries' => $timeseries,
            'top_violations' => $topViolations,
            'top_vehicles' => $topVehicles,
            'top_trailers' => $topTrailers,
            'monthly' => $months,
            'recent' => $recent,
        ]);
    }
}

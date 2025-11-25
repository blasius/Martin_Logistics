<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficFine;
use App\Models\TrafficFineViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class FinesAnalyticsController extends Controller
{
    /**
     * GET /api/portal/fines/analytics
     *
     * Query params:
     * - range: '7'|'30'|'90'|'custom' (default 30)
     * - from: 'YYYY-MM-DD' (when range=custom)
     * - to: 'YYYY-MM-DD'   (when range=custom)
     * - export: 'csv' -> returns CSV download instead of JSON
     */
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

        // Summary cards (period-limited)
        $totalCount = TrafficFine::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->count();
        $totalAmount = TrafficFine::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum(DB::raw('COALESCE(ticket_amount,0) + COALESCE(late_fee,0)'));

        $totalUnpaid = TrafficFine::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereRaw("UPPER(status) != 'PAID'")->count();

        $totalPaid = TrafficFine::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereRaw("UPPER(status) = 'PAID'")->count();

        // Timeseries by day
        $timeseriesRaw = TrafficFine::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as amount')
        )
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // build continuous days array
        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $row = $timeseriesRaw->has($key) ? $timeseriesRaw->get($key) : null;
            $dates[] = [
                'date' => $key,
                'count' => $row ? (int)$row->count : 0,
                'amount' => $row ? (float)$row->amount : 0.0,
            ];
        }

        // Top violations within range
        $topViolations = DB::table('traffic_fine_violations')
            ->join('traffic_fines','traffic_fine_violations.traffic_fine_id','traffic_fines.id')
            ->select('traffic_fine_violations.violation_name', DB::raw('COUNT(*) as occurrences'), DB::raw('SUM(COALESCE(traffic_fine_violations.fine_amount,0)) as total_amount'))
            ->whereBetween('traffic_fines.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('violation_name')
            ->orderByDesc('occurrences')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'violation_name' => $r->violation_name,
                'occurrences' => (int)$r->occurrences,
                'total_amount' => (float)$r->total_amount,
            ]);

        // Top vehicles & trailers
        $topVehicles = TrafficFine::select('plate_number', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as total_amount'))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('plate_number')
            ->where('plate_number','!=','')
            ->groupBy('plate_number')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['plate_number' => $r->plate_number, 'count' => (int)$r->count, 'total_amount' => (float)$r->total_amount]);

        $topTrailers = TrafficFine::select('plate_number', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as total_amount'))
            ->where('fineable_type', 'App\\Models\\Trailer')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('plate_number')
            ->where('plate_number','!=','')
            ->groupBy('plate_number')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['plate_number' => $r->plate_number, 'count' => (int)$r->count, 'total_amount' => (float)$r->total_amount]);

        // Monthly totals for chart (last 12 months relative to $to)
        $monthsBack = $to->copy()->subMonths(11);
        $monthlyRaw = TrafficFine::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(ticket_amount,0)+COALESCE(late_fee,0)) as amount'))
            ->whereDate('created_at', '>=', $monthsBack->toDateString())
            ->whereDate('created_at','<=', $to->toDateString())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        for ($m = $monthsBack->copy(); $m->lte($to); $m->addMonth()) {
            $key = $m->format('Y-m');
            if ($monthlyRaw->has($key)) {
                $r = $monthlyRaw->get($key);
                $months[] = ['month' => $key, 'count' => (int)$r->count, 'amount' => (float)$r->amount];
            } else {
                $months[] = ['month' => $key, 'count' => 0, 'amount' => 0.0];
            }
        }

        // Recent fines (in range)
        $recent = TrafficFine::with('violations','fineable')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
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

        // CSV export
        if ($request->get('export') === 'csv') {
            return $this->exportCsvRange($from, $to);
        }

        return response()->json([
            'summary' => [
                'total_count' => (int)$totalCount,
                'total_amount' => (float)$totalAmount,
                'total_unpaid' => (int)$totalUnpaid,
                'total_paid' => (int)$totalPaid,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'timeseries' => $dates,
            'top_violations' => $topViolations,
            'top_vehicles' => $topVehicles,
            'top_trailers' => $topTrailers,
            'monthly' => $months,
            'recent' => $recent,
        ]);
    }

    /**
     * Drill-down: return fines for a given day (YYYY-MM-DD).
     * GET /api/portal/fines/by-day?date=YYYY-MM-DD
     */
    public function byDay(Request $request)
    {
        $date = $request->get('date');
        if (! $date) {
            return response()->json(['error'=>'date is required'], 400);
        }
        $d = Carbon::parse($date);
        $query = TrafficFine::with('violations','fineable')
            ->whereDate('created_at', $d->toDateString())
            ->orderByDesc('created_at');

        $perPage = intval($request->get('per_page', 50));
        $page = intval($request->get('page', 1));
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($results);
    }

    /**
     * Export CSV for a date range (streamed)
     * Called when ?export=csv on /analytics
     */
    protected function exportCsvRange(Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'fines_'.$from->toDateString().'_to_'.$to->toDateString().'.csv';

        $callback = function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            // header
            fputcsv($handle, ['id','plate_number','ticket_number','ticket_amount','late_fee','paid_amount','status','issued_at','pay_by','created_at','violations']);

            $cursor = TrafficFine::with('violations')
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->orderBy('created_at')
                ->cursor();

            foreach ($cursor as $fine) {
                $violations = $fine->violations->map(fn($v)=> $v->violation_name.' ('.number_format($v->fine_amount).')')->implode('; ');
                fputcsv($handle, [
                    $fine->id,
                    $fine->plate_number,
                    $fine->ticket_number,
                    $fine->ticket_amount,
                    $fine->late_fee,
                    $fine->paid_amount,
                    $fine->status,
                    $fine->issued_at?->toDateString(),
                    $fine->pay_by?->toDateString(),
                    $fine->created_at->toDateTimeString(),
                    $violations,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Export CSV for a given day (drill-down).
     * GET /api/portal/fines/export-day?date=YYYY-MM-DD
     */
    public function exportDay(Request $request)
    {
        $date = $request->get('date');
        if (! $date) return response()->json(['error'=>'date required'], 400);
        $d = Carbon::parse($date);
        $filename = 'fines_'.$d->toDateString().'.csv';

        $callback = function () use ($d) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id','plate_number','ticket_number','ticket_amount','status','issued_at','pay_by','created_at','violations']);

            $cursor = TrafficFine::with('violations')
                ->whereDate('created_at', $d->toDateString())
                ->orderBy('created_at')
                ->cursor();

            foreach ($cursor as $fine) {
                $violations = $fine->violations->map(fn($v)=> $v->violation_name.' ('.number_format($v->fine_amount).')')->implode('; ');
                fputcsv($handle, [
                    $fine->id,
                    $fine->plate_number,
                    $fine->ticket_number,
                    $fine->ticket_amount,
                    $fine->status,
                    $fine->issued_at?->toDateString(),
                    $fine->pay_by?->toDateString(),
                    $fine->created_at->toDateTimeString(),
                    $violations,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}

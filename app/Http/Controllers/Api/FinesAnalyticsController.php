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

    public function byDay(Request $request)
    {
        $date = Carbon::parse($request->get('date'))->toDateString();
        return response()->json(
            TrafficFine::with('violations')
                ->whereDate('created_at', $date)
                ->orderByDesc('created_at')
                ->paginate($request->get('per_page', 20))
        );
    }

    public function byViolation(Request $request)
    {
        $name = $request->get('violation_name');
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();

        $query = TrafficFine::with('violations')
            ->whereHas('violations', fn($q) => $q->where('violation_name', $name))
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at');

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function exportDay(Request $request)
    {
        $date = Carbon::parse($request->get('date'))->toDateString();
        return $this->generateCsvStream("fines_$date.csv", TrafficFine::whereDate('created_at', $date));
    }

    public function exportViolationCsv(Request $request)
    {
        $name = $request->get('violation_name');
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();

        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $name))
            ->whereBetween('created_at', [$from, $to]);

        return $this->generateCsvStream("violation_export.csv", $query);
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

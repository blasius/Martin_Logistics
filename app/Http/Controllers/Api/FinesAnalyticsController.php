<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Closure;

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

        // Base Query for the selected period. Bounded by the police issue date,
        // not by when we imported the ticket — see TrafficFine::ISSUED_DATE_COLUMN.
        $baseQuery = TrafficFine::issuedBetween($from_date, $to_date);

        $summary = [
            // Outstanding debt, principal and penalty kept separate so the
            // amount attributable to late fees is visible.
            'outstanding_count' => (clone $baseQuery)->outstanding()->count(),
            'total_principal' => (float) (clone $baseQuery)->outstanding()->sum('ticket_amount'),
            'total_late_fees' => (float) (clone $baseQuery)->outstanding()->sum('late_fee'),

            // Overdue is decided purely by the portal Pay By date.
            'overdue_count' => (clone $baseQuery)->overdue()->count(),
            'overdue_amount' => round(
                (float) (clone $baseQuery)->overdue()->sum('ticket_amount')
                + (float) (clone $baseQuery)->overdue()->sum('late_fee'),
                2
            ),

            // Portal has already attached a penalty.
            'penalized_count' => (clone $baseQuery)->penalized()->count(),

            // Due soon: not yet overdue, no penalty yet, falling due shortly.
            'due_soon_count' => (clone $baseQuery)->outstanding()
                ->where('late_fee', 0)
                ->dueWithin(TrafficFine::DUE_SOON_DAYS)
                ->count(),
            'due_soon_amount' => (float) (clone $baseQuery)->outstanding()
                ->where('late_fee', 0)
                ->dueWithin(TrafficFine::DUE_SOON_DAYS)
                ->sum('ticket_amount'),

            'total_settled' => (clone $baseQuery)->settled()->count(),
            'total_count' => (clone $baseQuery)->count(),
            'from' => $from_date->toDateString(),
            'to' => $to_date->toDateString(),
        ];

        // Issuance history: tickets bucketed by the date the offence happened.
        $timeseriesRaw = TrafficFine::selectRaw('DATE(issued_at) as date, COUNT(*) as count')
            ->issuedBetween($from_date, $to_date)
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $dates[] = ['date' => $key, 'count' => (int) ($timeseriesRaw->get($key)->count ?? 0)];
        }

        // 4. Top Violations (ONLY OUTSTANDING)
        $topViolations = DB::table('traffic_fine_violations')
            ->join('traffic_fines', 'traffic_fine_violations.traffic_fine_id', '=', 'traffic_fines.id')
            ->select('traffic_fine_violations.violation_name', DB::raw('COUNT(*) as occurrences'))
            ->whereBetween('traffic_fines.issued_at', [$from_date, $to_date])
            ->where(fn($q) => $q->whereNull('traffic_fines.status')
                ->orWhereNotIn('traffic_fines.status', TrafficFine::SETTLED_STATUSES))
            ->groupBy('traffic_fine_violations.violation_name')
            ->orderByDesc('occurrences')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => $summary,
            'timeseries' => $dates,
            'top_violations' => $topViolations,
            // Fines with no issue date from the portal cannot be placed on the
            // timeline, so surface the gap rather than silently dropping them.
            'undated_count' => TrafficFine::whereNull('issued_at')->count(),
        ]);
    }

    public function byDay(Request $request) {
        $query = TrafficFine::issuedOn($request->get('date'));
        return $this->handleDrillDown($query, $request);
    }

    public function byViolation(Request $request) {
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();
        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $request->get('violation_name')))
            ->issuedBetween($from, $to);
        return $this->handleDrillDown($query, $request);
    }

    /**
     * CSV export of the single-day drill-down, honouring the same status
     * filters as the on-screen drill-down.
     */
    public function exportDay(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        return $this->streamCsv(
            TrafficFine::issuedOn($validated['date']),
            'fines_drilldown_' . Carbon::parse($validated['date'])->toDateString() . '.csv'
        );
    }

    private function handleDrillDown($query, $request) {
        if ($search = $request->get('search')) {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        $meta_counts = [
            'all'         => (clone $query)->count(),
            'paid'        => (clone $query)->settled()->count(),
            'outstanding' => (clone $query)->outstanding()->count(),
            'overdue'     => (clone $query)->overdue()->count(),
            'at_risk'     => (clone $query)->outstanding()->where($this->atRiskPredicate())->count(),
        ];

        // Default to risky fines
        $status = $request->get('status', 'at_risk');

        if ($status === 'at_risk') {
            $query->outstanding()->where($this->atRiskPredicate());
        } elseif ($status === 'overdue') {
            $query->overdue();
        } elseif ($status === 'paid') {
            $query->settled();
        } elseif ($status === 'outstanding') {
            $query->outstanding();
        }

        return response()->json([
            'results' => $query->with(['violations'])
                ->orderByRaw('pay_by IS NULL, pay_by ASC')
                ->paginate(20),
            'meta_counts' => $meta_counts
        ]);
    }

    /**
     * At risk = outstanding and either past the portal Pay By date, already
     * carrying a penalty, or falling due inside the warning window.
     */
    private function atRiskPredicate(): Closure
    {
        return function ($q) {
            $q->where('late_fee', '>', 0)
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('pay_by')
                        ->whereDate('pay_by', '<=', now()->addDays(TrafficFine::DUE_SOON_DAYS));
                });
        };
    }

    private function exportCsvRange($from, $to)
    {
        return $this->streamCsv(
            TrafficFine::issuedBetween($from, $to),
            'fines_debt_report_' . now()->format('Y-m-d') . '.csv'
        );
    }

    /**
     * Stream a fine query as CSV using the shared column set.
     *
     * Amounts report principal and late fee separately, with the total owed
     * being the sum of the two, and every timing column is derived from the
     * portal's Pay By date.
     */
    private function streamCsv($query, string $filename)
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function () use ($query) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Issued At', 'Pay By', 'Plate', 'Ticket', 'Status', 'Due State',
                'Days Until Pay By', 'Principal (FRW)', 'Late Fee (FRW)', 'Total Owed (FRW)',
                'Paid Amount (FRW)', 'Paid At', 'Location', 'Offences',
            ]);

            $query->with('violations')->chunk(200, function ($fines) use ($file) {
                foreach ($fines as $f) {
                    fputcsv($file, [
                        $f->issued_at?->toDateString(),
                        $f->pay_by?->toDateString(),
                        $f->plate_number,
                        $f->ticket_number,
                        $f->status,
                        $f->dueStateLabel(),
                        $f->daysUntilPayBy(),
                        (float) $f->ticket_amount,
                        (float) $f->late_fee,
                        $f->total_due,
                        (float) $f->paid_amount,
                        $f->paid_at?->toDateString(),
                        $f->location,
                        collect($f->violations)->pluck('violation_name')->implode(' | '),
                    ]);
                }
            });

            fclose($file);
        }, 200, $headers);
    }
}

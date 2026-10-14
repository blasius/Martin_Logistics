<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Closure;

class FinesAnalyticsController extends Controller
{
    /**
     * Preset ranges offered by the analytics screen. Anything longer than
     * MONTHLY_THRESHOLD_DAYS is charted by month rather than by day, since a
     * year of daily points is unreadable on an axis.
     */
    public const NAMED_RANGES = [
        '7' => ['unit' => 'day', 'value' => 7],
        '30' => ['unit' => 'day', 'value' => 30],
        '90' => ['unit' => 'day', 'value' => 90],
        '12m' => ['unit' => 'month', 'value' => 12],
    ];

    /** Spans wider than this are bucketed by month. */
    public const MONTHLY_THRESHOLD_DAYS = 92;

    /** Upper bound on a custom range, to keep aggregation bounded. */
    public const MAX_RANGE_DAYS = 1830;

    public function index(Request $request)
    {
        $range = (string) $request->get('range', '30');

        if ($range === 'custom') {
            $custom = $request->validate([
                'from' => 'nullable|date',
                'to' => 'nullable|date',
            ]);

            $to = isset($custom['to']) ? Carbon::parse($custom['to']) : Carbon::today();
            $from = isset($custom['from']) ? Carbon::parse($custom['from']) : $to->copy()->subDays(29);

            if ($from->gt($to)) {
                return response()->json(['message' => 'The start date must be on or before the end date.'], 422);
            }

            if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
                return response()->json([
                    'message' => 'That range is too large. Please select a span of '
                        . self::MAX_RANGE_DAYS . ' days or less.',
                ], 422);
            }
        } elseif (isset(self::NAMED_RANGES[$range])) {
            $preset = self::NAMED_RANGES[$range];
            $to = Carbon::today();
            $from = $preset['unit'] === 'month'
                ? $to->copy()->subMonths($preset['value'])->addDay()
                : $to->copy()->subDays($preset['value'] - 1);
        } else {
            // Unknown range falls back to the default rather than erroring,
            // so a stale bookmark still renders something sensible.
            $range = '30';
            $to = Carbon::today();
            $from = $to->copy()->subDays(29);
        }

        $from_date = $from->copy()->startOfDay();
        $to_date = $to->copy()->endOfDay();

        $monthly = $from_date->diffInDays($to_date) > self::MONTHLY_THRESHOLD_DAYS;

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
            'range' => $range,
            'bucket' => $monthly ? 'month' : 'day',
            'from' => $from_date->toDateString(),
            'to' => $to_date->toDateString(),
        ];

        // Issuance history, bucketed by the offence date.
        $dates = $this->buildTimeseries($from_date, $to_date, $monthly);

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

    /**
     * Build the issuance-history series, zero-filled so gaps in the data
     * render as flat stretches rather than a misleading straight line between
     * distant points.
     *
     * Short ranges chart by day; anything wider than
     * MONTHLY_THRESHOLD_DAYS charts by month, because a year of daily points
     * is unreadable on an axis. SUBSTR is used instead of DATE_FORMAT so the
     * query runs unchanged on both MySQL and SQLite.
     */
    private function buildTimeseries(Carbon $from, Carbon $to, bool $monthly): array
    {
        $column = TrafficFine::ISSUED_DATE_COLUMN;

        $counts = TrafficFine::selectRaw(
            $monthly
                ? "SUBSTR({$column}, 1, 7) as bucket, COUNT(*) as total"
                : "DATE({$column}) as bucket, COUNT(*) as total"
        )
            ->issuedBetween($from, $to)
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $series = [];

        if ($monthly) {
            for ($cursor = $from->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
                $key = $cursor->format('Y-m');
                $series[] = [
                    'date' => $key,
                    'label' => $cursor->format('M Y'),
                    'count' => (int) ($counts[$key] ?? 0),
                ];
            }
        } else {
            for ($cursor = $from->copy()->startOfDay(); $cursor->lte($to); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $series[] = [
                    'date' => $key,
                    'label' => $key,
                    'count' => (int) ($counts[$key] ?? 0),
                ];
            }
        }

        return $series;
    }

    public function byDay(Request $request) {
        $this->validateBucket($request);

        return $this->handleDrillDown($this->bucketQuery($request), $request);
    }

    /**
     * Validate a chart drill-down target: either a single day or a month.
     */
    private function validateBucket(Request $request): array
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m',
        ]);

        if (empty($validated['date']) && empty($validated['month'])) {
            throw ValidationException::withMessages([
                'date' => 'Either a date or a month must be supplied.',
            ]);
        }

        return $validated;
    }

    /**
     * Resolve the fines behind a chart point.
     *
     * Long ranges are charted by month, so the drill-down has to accept a
     * month bucket as well as a day bucket.
     */
    private function bucketQuery(Request $request)
    {
        if (!empty($request->month)) {
            $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

            return TrafficFine::issuedBetween($start, $start->copy()->endOfMonth());
        }

        return TrafficFine::issuedOn($request->date);
    }

    public function byViolation(Request $request) {
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to = Carbon::parse($request->get('to'))->endOfDay();
        $query = TrafficFine::whereHas('violations', fn($q) => $q->where('violation_name', $request->get('violation_name')))
            ->issuedBetween($from, $to);
        return $this->handleDrillDown($query, $request);
    }

    /**
     * CSV export of a chart drill-down (a single day or a whole month),
     * honouring the same status filters as the on-screen drill-down.
     */
public function exportDay(Request $request)
    {
        $this->validateBucket($request);

        $request->validate([
            'status' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        $query = $this->bucketQuery($request);

        $label = $request->month ?: Carbon::parse($request->date)->toDateString();

        return $this->streamCsv($query, "fines_drilldown_{$label}.csv");
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

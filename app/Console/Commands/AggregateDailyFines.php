<?php

namespace App\Console\Commands;

use App\Models\TrafficFine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AggregateDailyFines extends Command
{
    protected $signature = 'fines:aggregate
                            {date? : Day to aggregate, as Y-m-d (defaults to yesterday)}
                            {--backfill=90 : Also re-aggregate the preceding N days, since a ticket imported today can carry an issue date months in the past}';

    protected $description = 'Aggregate fines per issue date into daily_fine_stats (default yesterday)';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        // Late-arriving tickets carry an issue date that is already well in the
        // past, so a single-day run would leave those buckets permanently stale.
        $backfill = max(0, (int) $this->option('backfill'));
        $start = Carbon::parse($date)->subDays($backfill)->startOfDay();

        $rows = TrafficFine::query()
            ->selectRaw('DATE(' . TrafficFine::ISSUED_DATE_COLUMN . ') as date')
            ->selectRaw('fineable_type')
            ->selectRaw('fineable_id')
            ->selectRaw('COUNT(*) as ticket_count')
            ->selectRaw('SUM(ticket_amount) as total_amount')
            ->whereNotNull(TrafficFine::ISSUED_DATE_COLUMN)
            ->whereBetween(TrafficFine::ISSUED_DATE_COLUMN, [$start, Carbon::parse($date)->endOfDay()])
            ->whereNotNull('fineable_type')
            ->whereNotNull('fineable_id')
            ->groupBy('date', 'fineable_type', 'fineable_id')
            ->get();

        $touched = [];

        foreach ($rows as $r) {
            DB::table('daily_fine_stats')->updateOrInsert(
                ['date' => $r->date, 'statable_type' => $r->fineable_type, 'statable_id' => $r->fineable_id],
                [
                    'ticket_count' => $r->ticket_count,
                    'total_amount' => $r->total_amount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $touched[$r->date] = true;
        }

        // Clear buckets whose fines have since been settled or re-dated, so a
        // re-run converges on the truth instead of leaving stale counts behind.
        $stale = DB::table('daily_fine_stats')
            ->whereBetween('date', [$start->toDateString(), $date])
            ->when(!empty($touched), fn ($q) => $q->whereNotIn('date', array_keys($touched)))
            ->pluck('date')
            ->unique();

        foreach ($stale as $staleDate) {
            DB::table('daily_fine_stats')
                ->where('date', $staleDate)
                ->whereNotExists(function ($sub) use ($staleDate) {
                    $sub->select(DB::raw(1))
                        ->from('traffic_fines')
                        ->whereColumn('traffic_fines.fineable_id', 'daily_fine_stats.statable_id')
                        ->whereColumn('traffic_fines.fineable_type', 'daily_fine_stats.statable_type')
                        ->whereDate('traffic_fines.issued_at', $staleDate);
                })
                ->delete();
        }

        $scope = $backfill > 0
            ? "{$start->toDateString()} to {$date}"
            : $date;

        $this->info("Aggregated fines issued {$scope}: " . count($rows) . ' row(s) across ' . count($touched) . ' day(s).');

        return 0;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateDailyFines extends Command
{
    protected $signature = 'fines:aggregate {date?}';
    protected $description = 'Aggregate fines per day into daily_fine_stats (default yesterday)';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date'))->toDateString() : Carbon::yesterday()->toDateString();

        $rows = DB::table('traffic_fines')
            ->select(DB::raw("DATE(created_at) as date"), 'fineable_type', 'fineable_id', DB::raw("COUNT(*) as ticket_count"), DB::raw("SUM(ticket_amount) as total_amount"))
            ->whereDate('created_at', $date)
            ->groupBy('date', 'fineable_type', 'fineable_id')
            ->get();

        foreach ($rows as $r) {
            DB::table('daily_fine_stats')->updateOrInsert(
                ['date' => $r->date, 'statable_type' => $r->fineable_type, 'statable_id' => $r->fineable_id],
                ['ticket_count' => $r->ticket_count, 'total_amount' => $r->total_amount, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->info("Aggregated fines for {$date}");
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateDailyFines extends Command
{
    protected $signature = 'fines:aggregate {date?}';
    protected $description = 'Aggregate daily fine stats (defaults to yesterday)';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date'))->toDateString() : Carbon::yesterday()->toDateString();

        $rows = DB::table('traffic_fines')
            ->select(DB::raw("DATE(created_at) as date"), 'finedable_type', 'finedable_id', DB::raw("COUNT(*) as ticket_count"), DB::raw("SUM(ticket_amount) as total_amount"))
            ->whereDate('created_at', $date)
            ->groupBy('date', 'finedable_type', 'finedable_id')
            ->get();

        foreach ($rows as $r) {
            $vehicleId = null;
            $trailerId = null;

            if (str_contains($r->finedable_type, 'Vehicle')) {
                $vehicleId = $r->finedable_id;
            }

            if (str_contains($r->finedable_type, 'Trailer')) {
                $trailerId = $r->finedable_id;
            }

            DB::table('daily_fine_stats')->updateOrInsert(
                ['date' => $r->date, 'vehicle_id' => $vehicleId, 'trailer_id' => $trailerId],
                [
                    'ticket_count' => $r->ticket_count,
                    'total_amount' => $r->total_amount,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->info("Aggregated fines for {$date}");

        return 0;
    }
}

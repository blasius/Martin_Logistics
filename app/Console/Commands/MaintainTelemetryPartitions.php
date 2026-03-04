<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintainTelemetryPartitions extends Command
{
    protected $signature = 'telemetry:maintain-partitions';
    protected $description = 'Automatically create the next month\'s partition for telemetry_points';

    public function handle()
    {
        $targetDate = Carbon::now()->addMonth(); // Prepare for next month
        $pName = "p" . $targetDate->format('Y_m');
        $lessThan = $targetDate->copy()->addMonth()->format('Y-m-01');

        // Check if partition already exists to avoid SQL errors
        $exists = DB::select("
            SELECT PARTITION_NAME
            FROM information_schema.PARTITIONS
            WHERE TABLE_NAME = 'telemetry_points'
            AND PARTITION_NAME = ?", [$pName]);

        if (empty($exists)) {
            $this->info("Creating partition $pName...");

            // Since we have a MAXVALUE partition, we use REORGANIZE
            DB::statement("
                ALTER TABLE telemetry_points REORGANIZE PARTITION p_future INTO (
                    PARTITION $pName VALUES LESS THAN ('$lessThan'),
                    PARTITION p_future VALUES LESS THAN (MAXVALUE)
                )
            ");

            $this->info("Partition $pName created successfully.");
        } else {
            $this->info("Partition $pName already exists. Skipping.");
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveTelemetry extends Command
{
    protected $signature = 'telemetry:archive';
    protected $description = 'Move old telemetry to history and manage partitions';

    public function handle()
    {
        $this->info('Starting nightly telemetry archive...');

        // 1. Identify the cutoff (48 hours ago)
        $cutoff = now()->subHours(48);

        // 2. Move data to history
        // Note: We manually map location POINT to lat/lon for the history table
        DB::transaction(function () use ($cutoff) {
            DB::statement("
                INSERT INTO telemetry_history (id, vehicle_id, fuel, odometer, speed, ignition, pwr_ext, lat, lon, created_at)
                SELECT
                    id, vehicle_id, fuel, odometer, speed, ignition, pwr_ext,
                    ST_Y(location) as lat,
                    ST_X(location) as lon,
                    created_at
                FROM telemetry_recent
                WHERE created_at < ?
            ", [$cutoff]);

            // 3. Delete from recent
            DB::table('telemetry_recent')->where('created_at', '<', $cutoff)->delete();
        });

        $this->info('Data migration complete.');

        // 4. Manage MySQL Partitions
        $this->managePartitions();

        return Command::SUCCESS;
    }

    private function managePartitions()
    {
        // Add partition for tomorrow (to ensure we never hit a 'no partition' error)
        $tomorrow = now()->addDay()->format('Y-m-d');
        $partitionName = 'p' . now()->addDay()->format('Ymd');
        $timestamp = now()->addDays(2)->startOfDay()->timestamp;

        try {
            DB::statement("ALTER TABLE telemetry_history ADD PARTITION (
                PARTITION $partitionName VALUES LESS THAN ($timestamp)
            )");
            $this->info("Created partition $partitionName.");
        } catch (\Exception $e) {
            $this->warn("Partition $partitionName likely already exists.");
        }

        // Drop partitions older than 90 days
        $oldDate = now()->subDays(90)->format('Ymd');
        // You would typically query INFORMATION_SCHEMA.PARTITIONS here
        // and drop any where the name suffix is < $oldDate.
    }
}

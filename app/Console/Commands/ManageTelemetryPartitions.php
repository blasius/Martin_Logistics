<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManageTelemetryPartitions extends Command
{
    protected $signature = 'telemetry:manage-partitions';

    protected $description = 'Automatically rotate and archive telemetry_points partitions safely';

    protected int $retentionMonths = 24;
    protected int $futureMonthsToEnsure = 3;

    public function handle(): int
    {
        $this->info('Starting telemetry partition management...');

        try {
            $this->ensureFuturePartitions();
            $this->archiveOldPartitions();

            $this->info('Partition management completed successfully.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Partition management failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Future Partitions Exist
    |--------------------------------------------------------------------------
    */
    protected function ensureFuturePartitions(): void
    {
        $existingPartitions = $this->getExistingPartitions();

        for ($i = 0; $i < $this->futureMonthsToEnsure; $i++) {

            $monthStart = now()->addMonths($i)->startOfMonth();
            $nextMonth = $monthStart->copy()->addMonth();

            $partitionName = 'p' . $monthStart->format('Y_m');

            if (in_array($partitionName, $existingPartitions)) {
                continue;
            }

            $lessThanDate = $nextMonth->format('Y-m-01');

            $this->info("Creating future partition: {$partitionName}");

            DB::statement("
                ALTER TABLE telemetry_points
                ADD PARTITION (
                    PARTITION {$partitionName}
                    VALUES LESS THAN (TO_DAYS('{$lessThanDate}'))
                )
            ");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Archive and Drop Old Partitions
    |--------------------------------------------------------------------------
    */
    protected function archiveOldPartitions(): void
    {
        $existingPartitions = $this->getExistingPartitions();

        $cutoff = now()->subMonths($this->retentionMonths)->startOfMonth();

        foreach ($existingPartitions as $partitionName) {

            if (!preg_match('/^p(\d{4})_(\d{2})$/', $partitionName, $matches)) {
                continue;
            }

            $year = $matches[1];
            $month = $matches[2];

            $partitionDate = Carbon::createFromDate($year, $month, 1);

            // Never touch current or future partitions
            if ($partitionDate->gte(now()->startOfMonth())) {
                continue;
            }

            if ($partitionDate->gte($cutoff)) {
                continue;
            }

            $this->info("Archiving partition: {$partitionName}");

            $this->archivePartition($partitionName);

            $this->info("Dropping partition: {$partitionName}");

            DB::statement("
                ALTER TABLE telemetry_points
                DROP PARTITION {$partitionName}
            ");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Archive Partition Safely
    |--------------------------------------------------------------------------
    */
    protected function archivePartition(string $partitionName): void
    {
        // Temporary table for exchange
        $tempTable = 'telemetry_tmp_' . uniqid();

        // Create identical temporary table
        DB::statement("
            CREATE TABLE {$tempTable} LIKE telemetry_points_archive
        ");

        // Exchange partition into temp table
        DB::statement("
            ALTER TABLE telemetry_points
            EXCHANGE PARTITION {$partitionName}
            WITH TABLE {$tempTable}
        ");

        // Insert into archive table
        DB::statement("
            INSERT INTO telemetry_points_archive
            SELECT * FROM {$tempTable}
        ");

        // Drop temporary table
        DB::statement("DROP TABLE {$tempTable}");
    }

    /*
    |--------------------------------------------------------------------------
    | Get Existing Partitions
    |--------------------------------------------------------------------------
    */
    protected function getExistingPartitions(): array
    {
        $results = DB::select("
            SELECT PARTITION_NAME
            FROM INFORMATION_SCHEMA.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'telemetry_points'
            AND PARTITION_NAME IS NOT NULL
        ");

        return collect($results)
            ->pluck('PARTITION_NAME')
            ->toArray();
    }
}

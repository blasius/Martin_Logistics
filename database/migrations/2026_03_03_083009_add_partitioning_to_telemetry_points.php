<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Change TIMESTAMP to DATETIME and Update Primary Key
        // We do this in one statement to be efficient
        DB::statement("
        ALTER TABLE telemetry_points
        MODIFY recorded_at DATETIME NOT NULL,
        DROP PRIMARY KEY,
        ADD PRIMARY KEY (id, recorded_at)
    ");

        // 2. Generate Partition Strings
        $partitions = [];
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::now()->addYears(2)->endOfYear();

        while ($startDate <= $endDate) {
            $pName = "p" . $startDate->format('Y_m');
            $lessThanValue = $startDate->copy()->addMonth()->format('Y-m-01');
            $partitions[] = "PARTITION $pName VALUES LESS THAN ('$lessThanValue')";
            $startDate->addMonth();
        }

        $partitionSql = implode(",\n                ", $partitions);

        // 3. Execute partitioning
        DB::statement("
        ALTER TABLE telemetry_points
        PARTITION BY RANGE COLUMNS (recorded_at) (
            $partitionSql,
            PARTITION p_future VALUES LESS THAN (MAXVALUE)
        )
    ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE telemetry_points REMOVE PARTITIONING");
        DB::statement("ALTER TABLE telemetry_points DROP PRIMARY KEY, ADD PRIMARY KEY (id)");
    }
};

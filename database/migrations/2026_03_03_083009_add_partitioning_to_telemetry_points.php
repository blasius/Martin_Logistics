<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the Foreign Key Constraint
        // MySQL does not allow foreign keys on partitioned tables.
        Schema::table('telemetry_points', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
        });

        // 2. Change TIMESTAMP to DATETIME and Update Primary Key
        // Partitioning columns MUST be part of the Primary Key.
        DB::statement("
            ALTER TABLE telemetry_points
            MODIFY recorded_at DATETIME NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (id, recorded_at)
        ");

        // 3. Generate Partition Strings
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

        // 4. Execute partitioning
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
        // 1. Remove Partitioning
        DB::statement("ALTER TABLE telemetry_points REMOVE PARTITIONING");

        // 2. Restore original Primary Key
        DB::statement("ALTER TABLE telemetry_points DROP PRIMARY KEY, ADD PRIMARY KEY (id)");

        // 3. Re-add the Foreign Key constraint
        Schema::table('telemetry_points', function (Blueprint $table) {
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();
        });
    }
};

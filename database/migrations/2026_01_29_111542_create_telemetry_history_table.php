<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to handle the complex Partitioning requirements of MySQL
        DB::statement("
            CREATE TABLE telemetry_history (
                id BIGINT UNSIGNED NOT NULL,
                vehicle_id BIGINT UNSIGNED NOT NULL,
                fuel DECIMAL(10,2),
                odometer DECIMAL(14,2),
                speed DECIMAL(6,2),
                ignition TINYINT(1) DEFAULT 0,
                pwr_ext DECIMAL(6,3),
                lat DECIMAL(10,7),
                lon DECIMAL(10,7),
                created_at TIMESTAMP NOT NULL,
                PRIMARY KEY (id, created_at),
                INDEX vehicle_history_idx (vehicle_id, created_at)
            ) ENGINE=InnoDB
            PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
                PARTITION p_old VALUES LESS THAN (UNIX_TIMESTAMP('2026-01-01 00:00:00')),
                PARTITION p_future VALUES LESS THAN (MAXVALUE)
            );
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS telemetry_history");
    }
};

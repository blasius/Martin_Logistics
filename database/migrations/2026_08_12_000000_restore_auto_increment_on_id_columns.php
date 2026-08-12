<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Core tables whose id PRIMARY KEY lost AUTO_INCREMENT (any insert without an
     * explicit id fails with SQLSTATE 1364 "Field 'id' doesn't have a default value").
     *
     * telemetry_history is intentionally excluded: its id is part of a composite
     * PRIMARY KEY (id, created_at) and is populated by the telemetry ingestion.
     */
    private array $tables = [
        'trailer_assignments',
        'trailers',
        'trip_histories',
        'trips',
        'users',
        'vehicle_daily_stats',
        'vehicle_inspections',
        'vehicle_insurances',
        'vehicle_routes',
        'vehicles',
        'wialon_units',
        'telemetry_recent',
    ];

    public function up(): void
    {
        // Some of these ids are referenced by foreign keys; temporarily disable
        // FK checks so the MODIFY is allowed. Column types are unchanged, so the
        // constraints remain valid.
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($this->tables as $table) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($this->tables as $table) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
};

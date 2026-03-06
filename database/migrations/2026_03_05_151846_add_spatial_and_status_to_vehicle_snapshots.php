<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up from any previous failed runs
        Schema::dropIfExists('vehicle_snapshots_new');

        // 2. Create the shadow table
        Schema::create('vehicle_snapshots_new', function ($table) {
            $table->unsignedBigInteger('vehicle_id')->primary();

            $table->timestamp('last_seen_at')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // CRITICAL: Must be NOT NULL for Spatial Index
            $table->geometry('location', subtype: 'point', srid: 4326);

            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('fuel_level', 10, 2)->nullable();

            $table->boolean('ignition')->default(false);
            $table->boolean('is_moving')->default(false);
            $table->boolean('is_idle')->default(false);
            $table->boolean('is_overspeeding')->default(false);
            $table->boolean('is_off_route')->default(false);
            $table->boolean('low_fuel')->default(false);

            // Relational hooks
            $table->unsignedBigInteger('active_route_id')->nullable();
            $table->unsignedBigInteger('active_driver_id')->nullable();

            $table->timestamps();

            $table->spatialIndex('location');
            $table->foreign('active_route_id')->references('id')->on('routes')->nullOnDelete();
        });

        // 3. Transfer data with fallback for missing coordinates
        DB::statement("
        INSERT INTO vehicle_snapshots_new
        (vehicle_id, last_seen_at, latitude, longitude, location, speed, fuel_level, ignition, is_moving, is_overspeeding, low_fuel, created_at, updated_at)
        SELECT
        vehicle_id, last_seen_at, latitude, longitude,
        ST_GeomFromText(
            IF(latitude IS NOT NULL AND longitude IS NOT NULL,
               CONCAT('POINT(', longitude, ' ', latitude, ')'),
               'POINT(0 0)'),
            4326),
        speed, fuel_level, ignition, is_moving, is_overspeeding, low_fuel, created_at, updated_at
        FROM vehicle_snapshots
    ");

        // 4. Swap
        Schema::drop('vehicle_snapshots');
        Schema::rename('vehicle_snapshots_new', 'vehicle_snapshots');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_snapshots', function (Blueprint $table) {
            //
        });
    }
};

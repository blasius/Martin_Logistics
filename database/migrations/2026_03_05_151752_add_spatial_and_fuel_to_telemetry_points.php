<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up from the previous failed run if necessary
        Schema::dropIfExists('telemetry_points_new');

        // 1. Create the shadow table with the NOT NULL constraint
        Schema::create('telemetry_points_new', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehicle_id');
            $table->timestamp('recorded_at')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // CRITICAL: Must be NOT NULL for the Spatial Index to work
            $table->geometry('location', subtype: 'point', srid: 4326);

            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('fuel_level_raw', 10, 2)->nullable();
            $table->decimal('fuel_change', 10, 2)->default(0);
            $table->decimal('external_voltage', 6, 3)->nullable();
            $table->boolean('ignition')->default(false);
            $table->boolean('is_idle')->default(false);
            $table->unsignedTinyInteger('gsm_signal')->nullable();
            $table->decimal('engine_hours', 12, 3)->nullable();
            $table->decimal('odometer', 14, 2)->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();

            // Now the index will succeed
            $table->spatialIndex('location');
        });

        // 2. Transfer data with a fallback for NULL coordinates
        // If lat/lon is missing, we use POINT(0 0) to satisfy the NOT NULL constraint
        DB::statement("
        INSERT INTO telemetry_points_new
        (id, vehicle_id, recorded_at, latitude, longitude, location, altitude, heading, speed, fuel_level_raw, external_voltage, ignition, gsm_signal, engine_hours, odometer, created_at, updated_at)
        SELECT
        id, vehicle_id, recorded_at, latitude, longitude,
        ST_GeomFromText(
            IF(latitude IS NOT NULL AND longitude IS NOT NULL,
               CONCAT('POINT(', longitude, ' ', latitude, ')'),
               'POINT(0 0)'),
            4326),
        altitude, heading, speed, fuel_level_raw, external_voltage, ignition, gsm_signal, engine_hours, odometer, created_at, updated_at
        FROM telemetry_points
    ");

        // 3. Swap
        Schema::drop('telemetry_points');
        Schema::rename('telemetry_points_new', 'telemetry_points');
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telemetry_points', function (Blueprint $table) {
            //
        });
    }
};

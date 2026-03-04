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
        Schema::create('telemetry_points_archive', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->unsignedBigInteger('vehicle_id');
            $table->timestamp('recorded_at');

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();

            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('fuel_level_raw', 10, 2)->nullable();
            $table->decimal('external_voltage', 6, 3)->nullable();
            $table->boolean('ignition')->default(false);
            $table->unsignedTinyInteger('gsm_signal')->nullable();
            $table->decimal('engine_hours', 12, 3)->nullable();
            $table->decimal('odometer', 14, 2)->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_points_archive');
    }
};

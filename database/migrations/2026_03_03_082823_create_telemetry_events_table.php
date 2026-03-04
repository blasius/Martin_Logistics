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
        Schema::create('telemetry_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('telemetry_point_id')->nullable();

            $table->string('type');
            // overspeed, fuel_refill, fuel_drain,
            // harsh_acceleration, harsh_braking,
            // ignition_on, ignition_off,
            // geofence_entry, geofence_exit,
            // route_deviation

            $table->decimal('value', 12, 2)->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('occurred_at')->index();

            $table->timestamps();

            $table->index(['vehicle_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_events');
    }
};

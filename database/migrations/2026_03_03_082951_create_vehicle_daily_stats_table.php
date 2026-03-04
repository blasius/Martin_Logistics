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
        Schema::create('vehicle_daily_stats', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('vehicle_id');
            $table->date('date');

            $table->decimal('distance_km', 12, 2)->default(0);
            $table->decimal('fuel_consumed', 12, 2)->default(0);
            $table->decimal('engine_hours', 12, 2)->default(0);

            $table->unsignedInteger('overspeed_count')->default(0);
            $table->unsignedInteger('harsh_events_count')->default(0);

            $table->timestamps();

            $table->unique(['vehicle_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_daily_stats');
    }
};

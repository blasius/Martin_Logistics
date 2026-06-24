<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_fuel_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('fuel_used', 10, 2);
            $table->decimal('expected_consumption', 10, 2);
            $table->decimal('variance_liters', 10, 2);
            $table->decimal('variance_percent', 6, 2);
            $table->enum('flag', ['normal', 'caution', 'excessive'])->default('normal');
            $table->timestamp('analysed_at')->nullable();
            $table->timestamps();

            $table->unique('trip_id');
        });

        Schema::create('driver_fuel_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('avg_variance_percent', 6, 2);
            $table->integer('total_trips')->default(0);
            $table->integer('flagged_trips')->default(0);
            $table->enum('rating', ['good', 'average', 'poor'])->default('average');
            $table->timestamps();

            $table->unique(['driver_id', 'period_start', 'period_end'], 'dfr_driver_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_ratings');
        Schema::dropIfExists('trip_fuel_analyses');
    }
};

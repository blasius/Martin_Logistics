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
        Schema::create('vehicle_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->primary();

            $table->timestamp('last_seen_at')->index();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('fuel_level', 10, 2)->nullable();

            $table->boolean('ignition')->default(false);
            $table->boolean('is_moving')->default(false);
            $table->boolean('is_overspeeding')->default(false);
            $table->boolean('low_fuel')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_snapshots');
    }
};

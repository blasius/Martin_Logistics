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
        Schema::create('vehicle_geofence_states', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('geofence_id');

            $table->boolean('is_inside')->default(false);
            $table->timestamp('last_transition_at')->nullable();

            $table->primary(['vehicle_id', 'geofence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_geofence_states');
    }
};

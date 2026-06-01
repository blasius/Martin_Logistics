<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telemetry_points', function (Blueprint $table) {
            $table->unique(
                ['vehicle_id', 'recorded_at'],
                'telemetry_points_vehicle_recorded_at_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('telemetry_points', function (Blueprint $table) {
            $table->dropUnique('telemetry_points_vehicle_recorded_at_unique');
        });
    }
};

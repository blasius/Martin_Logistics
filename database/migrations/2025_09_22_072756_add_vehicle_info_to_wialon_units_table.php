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
        Schema::table('wialon_units', function (Blueprint $table) {
            $table->decimal('speed')
                ->after('last_lon')
                ->nullable();

            $table->boolean('ignition')
                ->after('speed')
                ->nullable();

            $table->decimal('gps_voltage')
                ->after('ignition')
                ->nullable();
            $table->decimal('vehicle_voltage')
                ->after('gps_voltage')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wialon_units', function (Blueprint $table) {
            $table->dropColumn([
                'speed',
                'ignition',
                'gps_voltage',
                'vehicle_voltage',
            ]);
        });
    }
};

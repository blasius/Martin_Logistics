<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('fuel_calibrations', function (Blueprint $table) {
            // We use this to know if we need to re-populate the JSON data
            $table->unsignedBigInteger('last_wialon_mt')->nullable()->after('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_calibrations', function (Blueprint $table) {
            //
        });
    }
};

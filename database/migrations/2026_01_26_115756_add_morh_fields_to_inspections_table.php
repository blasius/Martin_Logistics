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
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            // Adds inspected_id and inspected_type
            $table->nullableMorphs('inspected');
            $table->foreignId('inspected_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            //
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_recent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();

            $table->decimal('fuel', 10, 2);
            $table->decimal('odometer', 14, 2);
            $table->decimal('speed', 6, 2)->nullable();
            $table->boolean('ignition')->default(false);
            $table->decimal('pwr_ext', 6, 3)->nullable();

            // MySQL Spatial Column: use geometry or raw point
            $table->geometry('location', subtype: 'point')->isSRID(4326);

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['vehicle_id', 'created_at']);
            $table->spatialIndex('location'); // Critical for proximity checks
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_recent');
    }
};

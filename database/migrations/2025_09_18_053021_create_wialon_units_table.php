<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wialon_units', function (Blueprint $table) {
            $table->id();
            // link to our canonical vehicle (optional)
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
            // Wialon unit id (string or integer depending on API)
            $table->string('wialon_id')->unique();
            // Unit name from Wialon (nm)
            $table->string('name')->nullable();
            // hardware uid / imei
            $table->string('uid')->nullable();
            // device type
            $table->string('device_type')->nullable();
            // latest position and timestamp (nullable until we sync positions)
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lon', 10, 7)->nullable();
            $table->timestamp('last_update')->nullable();
            // sync metadata
            $table->boolean('is_linked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wialon_units');
    }
};

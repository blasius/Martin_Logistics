<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add to the Hot Table
        Schema::table('telemetry_recent', function (Blueprint $blueprint) {
            $blueprint->string('event_type')->nullable()->after('pwr_ext'); // 'theft', 'refill', etc.
            $blueprint->decimal('event_value', 10, 2)->nullable()->after('event_type'); // Liters stolen/added
        });

        // Add to the Cold Table (History)
        Schema::table('telemetry_history', function (Blueprint $blueprint) {
            $blueprint->string('event_type')->nullable()->after('pwr_ext');
            $blueprint->decimal('event_value', 10, 2)->nullable()->after('event_type');
        });
    }
};

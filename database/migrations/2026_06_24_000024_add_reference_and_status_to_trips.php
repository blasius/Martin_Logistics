<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add reference column
        if (!Schema::hasColumn('trips', 'reference')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->string('reference')->nullable()->after('id');
            });
        }

        // Change status from enum to string for flexibility
        DB::statement("ALTER TABLE trips MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pre_departure'");

        // Add reference values for existing rows
        $year = now()->year;
        $count = \App\Models\Trip::count();
        $trips = \App\Models\Trip::whereNull('reference')->get();
        foreach ($trips as $i => $trip) {
            $trip->reference = 'T-' . $year . '-' . str_pad($count - $i, 5, '0', STR_PAD_LEFT);
            $trip->saveQuietly();
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('stop_started_at')->nullable();
            $table->decimal('stop_latitude', 10, 7)->nullable();
            $table->decimal('stop_longitude', 10, 7)->nullable();
            $table->timestamp('stop_alerted_at')->nullable();
            $table->unsignedInteger('total_rest_minutes')->default(0);
            $table->unsignedInteger('unexpected_stop_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'stop_started_at',
                'stop_latitude',
                'stop_longitude',
                'stop_alerted_at',
                'total_rest_minutes',
                'unexpected_stop_count',
            ]);
        });
    }
};
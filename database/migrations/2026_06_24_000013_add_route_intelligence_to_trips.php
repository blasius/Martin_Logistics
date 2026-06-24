<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('planned_distance_km', 10, 2)->nullable();
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->decimal('start_odometer', 10, 1)->nullable();
            $table->decimal('end_odometer', 10, 1)->nullable();
            $table->boolean('is_deviated')->default(false);
            $table->timestamp('deviation_detected_at')->nullable();
            $table->integer('deviation_duration_minutes')->nullable();
            $table->decimal('deviation_max_distance_meters', 10, 2)->nullable();
            $table->foreignId('auto_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auto_ticket_id');
            $table->dropConstrainedForeignId('route_id');
            $table->dropConstrainedForeignId('dispatcher_id');
            $table->dropColumn([
                'planned_distance_km', 'actual_distance_km',
                'start_odometer', 'end_odometer',
                'is_deviated', 'deviation_detected_at',
                'deviation_duration_minutes', 'deviation_max_distance_meters',
            ]);
        });
    }
};

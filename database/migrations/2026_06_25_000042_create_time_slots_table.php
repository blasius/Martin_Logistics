<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->morphs('schedulable');
            $table->string('title');
            $table->string('type')->comment('pickup, delivery, driver_shift, maintenance, dock_reservation, trip');
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status')->default('scheduled')->comment('scheduled, confirmed, in_progress, completed, cancelled');
            $table->text('notes')->nullable();
            $table->string('color')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_time', 'end_time']);
            $table->index(['vehicle_id', 'start_time']);
            $table->index(['driver_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};

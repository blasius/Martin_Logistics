<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->enum('type', ['oil_change', 'tire_rotation', 'brake_check', 'inspection', 'general']);
            $table->decimal('interval_km', 10, 1)->nullable()->comment('km between services');
            $table->integer('interval_days')->nullable()->comment('days between services');
            $table->timestamp('last_done_at')->nullable();
            $table->decimal('last_done_km', 10, 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};

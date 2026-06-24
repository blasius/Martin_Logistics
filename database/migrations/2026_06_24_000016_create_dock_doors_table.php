<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dock_doors', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_type'); // loading_dock, unload_dock, fueling_bay, car_wash, workshop
            $table->boolean('is_occupied')->default(false);
            $table->foreignId('current_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('current_queue_id')->nullable()->constrained('service_queues')->nullOnDelete();
            $table->timestamp('occupied_since')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dock_doors');
    }
};

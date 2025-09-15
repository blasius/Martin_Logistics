<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Snapshots
            $table->string('vehicle_plate_snapshot');
            $table->string('driver_name_snapshot');
            $table->string('trailer_plate_snapshot')->nullable();

            $table->enum('status', ['pending', 'assigned', 'on_route', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('arrival_time')->nullable();

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};

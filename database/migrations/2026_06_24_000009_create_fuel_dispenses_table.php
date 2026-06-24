<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_dispenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('driver_id')->nullable()->constrained('drivers');
            $table->foreignId('tank_id')->nullable()->constrained('fuel_tanks');
            $table->decimal('quantity', 10, 2);
            $table->decimal('odometer_at_dispense', 10, 1)->nullable();
            $table->timestamp('dispensed_at');
            $table->foreignId('dispensed_by')->constrained('users');
            $table->foreignId('trip_id')->nullable()->constrained('trips');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->decimal('calculated_amount', 10, 2)->nullable()->comment('System-recommended amount from 3.2 logic');
            $table->string('override_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_dispenses');
    }
};

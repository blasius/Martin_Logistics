<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('truck_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_person_id')->constrained('users')->cascadeOnDelete();
            $table->string('cargo_type');
            $table->decimal('tonnage', 10, 2);
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->date('expected_pickup_date');
            $table->date('expected_delivery_date');
            $table->text('special_requirements')->nullable();
            $table->decimal('agreed_rate', 15, 2)->nullable();
            $table->string('client_reference')->nullable();
            $table->string('payment_status')->default('pending')->comment('pending, paid');
            $table->string('status')->default('draft')
                ->comment('draft, submitted, truck_assigned, in_progress, completed, cancelled');
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('assigned_trailer_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->boolean('fuel_confirmed')->default(false);
            $table->decimal('fuel_liters', 10, 2)->nullable();
            $table->decimal('odometer_start', 10, 2)->nullable();
            $table->boolean('documents_uploaded')->default(false);
            $table->boolean('instructions_provided')->default(false);
            $table->boolean('inspection_confirmed')->default(false);
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ready_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_preparations');
        Schema::dropIfExists('truck_requests');
    }
};

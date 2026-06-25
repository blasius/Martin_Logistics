<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_line_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('shipping_line');
            $table->string('name');
            $table->unsignedInteger('free_demurrage_days')->default(0);
            $table->unsignedInteger('free_detention_days')->default(0);
            $table->json('demurrage_tiers')->nullable();
            $table->json('detention_tiers')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_id')->unique();
            $table->string('size'); // 20ft, 40ft, 40hc
            $table->string('type'); // dry, reefer, open_top, flat_rack, tank
            $table->string('owner_type'); // private, shipping_line
            $table->foreignId('shipping_line_contract_id')->nullable()->constrained('shipping_line_contracts');
            $table->boolean('is_owned')->default(true);
            $table->decimal('purchase_value', 12, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('current_status'); // at_port, in_transit, at_warehouse, at_customer, empty_returned, damaged, scrapped
            $table->nullableMorphs('current_location', 'cont_current_location_index');
            $table->string('last_known_gps')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('container_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained('containers');
            $table->nullableMorphs('from_location', 'cm_from_location_index');
            $table->nullableMorphs('to_location', 'cm_to_location_index');
            $table->string('movement_type'); // port_pickup, delivery_to_customer, return_to_depot, reposition, transfer
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->foreignId('driver_id')->nullable()->constrained('drivers');
            $table->foreignId('trip_id')->nullable()->constrained('trips');
            $table->string('seal_number')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('container_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained('containers');
            $table->string('penalty_type'); // demurrage, detention
            $table->unsignedInteger('days_overdue');
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_penalties');
        Schema::dropIfExists('container_movements');
        Schema::dropIfExists('containers');
        Schema::dropIfExists('shipping_line_contracts');
    }
};

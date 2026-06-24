<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_tanks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('capacity', 10, 2);
            $table->decimal('current_level', 10, 2)->default(0);
            $table->enum('fuel_type', ['diesel', 'petrol']);
            $table->decimal('reorder_threshold', 10, 2)->nullable();
            $table->timestamp('last_calibrated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('fuel_tanks')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('vendors');
            $table->enum('fuel_type', ['diesel', 'petrol']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 14, 2);
            $table->string('invoice_reference')->nullable();
            $table->timestamp('delivered_at');
            $table->foreignId('received_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_deliveries');
        Schema::dropIfExists('fuel_tanks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('part_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_quantity', 12, 2)->nullable();
            $table->decimal('estimated_unit_price', 12, 2)->nullable();
            $table->decimal('estimated_total', 14, 2)->nullable();
            $table->decimal('actual_quantity', 12, 2)->nullable();
            $table->decimal('actual_unit_price', 12, 2)->nullable();
            $table->decimal('actual_total', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_request_items');
    }
};

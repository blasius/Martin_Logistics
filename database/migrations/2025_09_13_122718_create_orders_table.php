<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique(); // internal ref no.
            $table->string('origin');              // pickup location
            $table->string('destination');         // dropoff location
            $table->date('pickup_date')->nullable();
            $table->enum('status', [
                'draft',
                'confirmed',
                'in_transit',
                'delivered',
                'cancelled',
            ])->default('draft');
            $table->decimal('price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

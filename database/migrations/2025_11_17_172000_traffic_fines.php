<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_fines', function (Blueprint $table) {
            $table->id();

            // polymorphic owner: Vehicle or Trailer
            $table->nullableMorphs('fineable'); // fineable_type, fineable_id

            $table->string('ticket_number')->nullable()->index();
            $table->decimal('ticket_amount', 14, 2)->nullable();
            $table->decimal('late_fee', 14, 2)->nullable();
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('issued_at')->nullable();
            $table->date('pay_by')->nullable();

            // lifecycle + payments
            $table->enum('status', ['PENDING','PAID','CANCELLED','DISPUTED'])->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();

            // friendly fields
            $table->string('plate_number')->nullable()->index();
            $table->string('location')->nullable();

            // keep raw API payload
            $table->json('raw')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_fines');
    }
};

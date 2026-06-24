<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('expense_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('location')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->string('expense_class');
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('proof_of_payment_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('expense_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

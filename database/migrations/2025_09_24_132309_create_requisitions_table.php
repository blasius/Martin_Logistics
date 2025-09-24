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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('expense_type_id')->nullable()->constrained('expense_types')->nullOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('related_trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->string('status')->default('pending_finance')->index();
            $table->foreignId('assigned_finance_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('voucher_number')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};

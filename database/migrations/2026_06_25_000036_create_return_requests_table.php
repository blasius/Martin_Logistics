<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->string('pickup_address')->nullable();
            $table->date('pickup_date')->nullable();
            $table->foreignId('pickup_trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->date('received_at')->nullable();
            $table->string('disposition')->nullable();
            $table->foreignId('credit_note_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->string('condition')->nullable();
            $table->string('disposition')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
    }
};

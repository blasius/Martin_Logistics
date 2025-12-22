<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            // Who created the ticket (driver or client)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Category
            $table->foreignId('support_category_id')->constrained()->restrictOnDelete();

            // Optional assignment
            $table->foreignId('assigned_to')->nullable()->references('id')->on('users')->nullOnDelete();

            // Polymorphic subject (vehicle, trip, invoice… future-proof)
            $table->nullableMorphs('subject');

            $table->string('title');
            $table->text('description');

            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'waiting', 'resolved', 'closed'])->default('open');

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};

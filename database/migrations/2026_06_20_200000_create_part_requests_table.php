<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('repair_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('urgency')->default('normal')->comment('normal, urgent');
            $table->string('status', 50)->default('pending_clerk')
                ->comment('pending_clerk, pending_workshop_manager, pending_logistics_manager, pending_ops_manager, approved, rejected');
            $table->unsignedTinyInteger('current_approval_level')->default(1)
                ->comment('1=Clerk, 2=Workshop Manager, 3=Logistics Manager, 4=Ops Manager');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('part_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('approval_level')->comment('clerk, workshop_manager, logistics_manager, ops_manager');
            $table->string('action')->comment('approved, rejected');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_request_approvals');
        Schema::dropIfExists('part_requests');
    }
};

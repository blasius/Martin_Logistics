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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('approver_role')->nullable();
            $table->unsignedTinyInteger('stage')->default(1)->comment('1 or 2 for two-level approval');
            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};

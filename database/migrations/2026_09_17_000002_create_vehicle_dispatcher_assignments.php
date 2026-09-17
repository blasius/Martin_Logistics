<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_dispatcher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('dispatcher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'deleted_at']);
            $table->index(['dispatcher_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_dispatcher_assignments');
    }
};
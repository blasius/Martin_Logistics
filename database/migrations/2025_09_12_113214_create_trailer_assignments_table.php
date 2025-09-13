<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trailer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trailer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->default(now());
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'trailer_id', 'assigned_at'], 'vehicle_trailer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trailer_assignments');
    }
};

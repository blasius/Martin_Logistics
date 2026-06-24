<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('service_type'); // offload, wash, workshop, fuel
            $table->string('priority')->default('normal'); // normal, high, critical
            $table->string('status')->default('queued'); // queued, in_progress, completed, skipped
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->json('coordinates')->nullable(); // lat/lng at submission
            $table->boolean('geofence_verified')->default(false);
            $table->integer('position')->nullable(); // manual queue position override
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_queues');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->string('classification')->default('unexpected'); // expected|unexpected
            $table->string('reason')->nullable(); // delivery|yard|unexpected
            $table->boolean('is_off_corridor')->default(false);
            $table->decimal('distance_from_route_meters', 10, 2)->nullable();

            $table->timestamp('alert_sent_at')->nullable();
            $table->unsignedInteger('alert_count')->default(0);
            $table->string('notes')->nullable();

            $table->index(['started_at', 'classification']);
            $table->index(['vehicle_id', 'started_at']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
    }
};
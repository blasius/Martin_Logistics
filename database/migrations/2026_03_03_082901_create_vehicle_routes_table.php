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
        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('route_id');

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_routes');
    }
};

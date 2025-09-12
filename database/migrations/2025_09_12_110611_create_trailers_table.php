<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trailers', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('chassis_number')->nullable();
            $table->decimal('capacity_weight', 8, 2)->nullable(); // in tons or kg
            $table->string('type')->nullable(); // flatbed, tanker, etc.
            $table->enum('status', ['active', 'in_maintenance'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trailers');
    }
};

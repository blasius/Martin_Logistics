<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_fine_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_fine_id')->constrained('traffic_fines')->cascadeOnDelete();
            $table->string('violation_name')->nullable();
            $table->string('violation_name_fr')->nullable();
            $table->string('violation_name_local')->nullable();
            $table->decimal('fine_amount', 14, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_fine_violations');
    }
};

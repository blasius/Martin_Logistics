<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_fine_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('trailer_id')->nullable()->constrained('trailers')->nullOnDelete();
            $table->integer('ticket_count')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['date', 'vehicle_id', 'trailer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_fine_stats');
    }
};

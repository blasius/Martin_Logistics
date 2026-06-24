<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamp('check_out_at')->nullable();
            $table->string('purpose'); // loading, unloading, fueling, washing, workshop, parking
            $table->foreignId('dock_door_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_queue_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yard_entries');
    }
};

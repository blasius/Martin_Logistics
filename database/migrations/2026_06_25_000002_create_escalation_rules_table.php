<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_source', 50);          // auto_route_deviation, auto_delay, auto_fuel_flag, or * for all
            $table->unsignedTinyInteger('level');          // 1, 2, 3, 4
            $table->unsignedSmallInteger('escalate_after_minutes');
            $table->string('assign_to_role');              // Spatie role name
            $table->timestamps();

            $table->unique(['ticket_source', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};

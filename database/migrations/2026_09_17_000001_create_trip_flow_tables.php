<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_flow_states', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trip_flow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_state_id')->nullable()->constrained('trip_flow_states')->nullOnDelete();
            $table->foreignId('to_state_id')->nullable()->constrained('trip_flow_states')->nullOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('label');
            $table->string('trigger')->default('any')
                ->comment('any, driver, dispatcher, system, manual');
            $table->json('roles')->nullable();
            $table->boolean('records_departure_time')->default(false);
            $table->boolean('records_arrival_time')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_flow_transitions');
        Schema::dropIfExists('trip_flow_states');
    }
};
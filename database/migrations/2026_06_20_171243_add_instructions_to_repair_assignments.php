<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_assignments', function (Blueprint $table) {
            $table->text('instructions')->nullable();
            $table->string('status')->default('assigned');
            $table->text('completed_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('repair_assignments', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'status', 'completed_note']);
        });
    }
};

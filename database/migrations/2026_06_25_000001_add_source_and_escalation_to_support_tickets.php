<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('source', 50)->default('manual')->after('status');
            $table->unsignedTinyInteger('escalation_level')->nullable()->after('source');
            $table->timestamp('escalated_at')->nullable()->after('escalation_level');
            $table->foreignId('escalated_to_id')->nullable()->after('escalated_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['source', 'escalation_level', 'escalated_at', 'escalated_to_id']);
        });
    }
};

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
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->timestamp('approval_requested_at')->nullable()->after('submitted_at');
            $table->foreignId('approval_requested_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_requested_by');
            $table->dropColumn('approval_requested_at');
        });
    }
};

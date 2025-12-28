<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {

            // Human-readable ticket number
            $table->string('reference')
                ->nullable()
                ->after('id')
                ->unique();

            // SLA tracking
            $table->timestamp('first_response_at')
                ->nullable()
                ->after('status');

            $table->timestamp('due_at')
                ->nullable()
                ->after('first_response_at');

            // Performance: polymorphic subject lookup
            $table->index(
                ['subject_type', 'subject_id'],
                'support_tickets_subject_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {

            $table->dropIndex('support_tickets_subject_index');

            $table->dropColumn([
                'reference',
                'first_response_at',
                'due_at',
            ]);
        });
    }
};

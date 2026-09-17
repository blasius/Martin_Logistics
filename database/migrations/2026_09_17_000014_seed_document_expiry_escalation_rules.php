<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed escalation rules for document-expiry auto-tickets (point 14).
     */
    public function up(): void
    {
        $rules = [
            ['ticket_source' => 'auto_document_expiry', 'level' => 1, 'escalate_after_minutes' => 1440, 'assign_to_role' => 'Logistics Manager'],
            ['ticket_source' => 'auto_document_expiry', 'level' => 2, 'escalate_after_minutes' => 4320, 'assign_to_role' => 'Director of Operations'],
            ['ticket_source' => 'auto_document_expiry', 'level' => 3, 'escalate_after_minutes' => 10080, 'assign_to_role' => 'Managing Director'],
        ];

        foreach ($rules as $rule) {
            DB::table('escalation_rules')->updateOrInsert(
                ['ticket_source' => $rule['ticket_source'], 'level' => $rule['level']],
                $rule + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('escalation_rules')->where('ticket_source', 'auto_document_expiry')->delete();
    }
};

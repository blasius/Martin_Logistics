<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every fine analytics query filters and groups by the police issue date, so
 * it needs an index. created_at is not a usable substitute for reporting: it
 * records our sync batch rather than the offence date.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('traffic_fines')) {
            return;
        }

        Schema::table('traffic_fines', function (Blueprint $table) {
            $table->index('issued_at', 'traffic_fines_issued_at_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('traffic_fines')) {
            return;
        }

        Schema::table('traffic_fines', function (Blueprint $table) {
            $table->dropIndex('traffic_fines_issued_at_index');
        });
    }
};
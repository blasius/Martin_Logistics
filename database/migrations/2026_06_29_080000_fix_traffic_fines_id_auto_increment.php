<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['traffic_fines', 'traffic_fine_violations'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
                } catch (\Throwable) {
                    // may already be correct
                }
            }
        }

        if (Schema::hasTable('traffic_fines')) {
            Schema::table('traffic_fines', function (Blueprint $table) {
                $table->string('fineable_type')->nullable()->change();
                $table->unsignedBigInteger('fineable_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('traffic_fines', function (Blueprint $table) {
            $table->string('fineable_type')->nullable(false)->change();
            $table->unsignedBigInteger('fineable_id')->nullable(false)->change();
        });
    }
};

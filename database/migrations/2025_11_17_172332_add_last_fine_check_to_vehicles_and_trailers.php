<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->timestamp('last_fine_check_at')->nullable()->after('updated_at');
            });
        }

        if (Schema::hasTable('trailers')) {
            Schema::table('trailers', function (Blueprint $table) {
                $table->timestamp('last_fine_check_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('last_fine_check_at');
            });
        }
        if (Schema::hasTable('trailers')) {
            Schema::table('trailers', function (Blueprint $table) {
                $table->dropColumn('last_fine_check_at');
            });
        }
    }
};

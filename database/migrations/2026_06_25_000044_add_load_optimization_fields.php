<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('volume_capacity', 10, 2)->nullable()->after('capacity')
                ->comment('Cubic meters');
            $table->decimal('max_payload', 10, 2)->nullable()->after('volume_capacity')
                ->comment('Maximum payload in kg');
            $table->decimal('max_trailer_weight', 10, 2)->nullable()->after('max_payload');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('volume_m3', 10, 2)->nullable()->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['volume_capacity', 'max_payload', 'max_trailer_weight']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('volume_m3');
        });
    }
};

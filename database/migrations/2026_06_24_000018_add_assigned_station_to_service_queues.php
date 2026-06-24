<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_queues', function (Blueprint $table) {
            $table->string('assigned_station')->nullable()->after('position');
            $table->string('driver_phone')->nullable()->after('assigned_station');
        });
    }

    public function down(): void
    {
        Schema::table('service_queues', function (Blueprint $table) {
            $table->dropColumn(['assigned_station', 'driver_phone']);
        });
    }
};

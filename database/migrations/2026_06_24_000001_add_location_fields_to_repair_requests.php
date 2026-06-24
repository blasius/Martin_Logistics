<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->json('coordinates')->nullable()->after('description');
            $table->boolean('geofence_verified')->default(false)->after('coordinates');
            $table->json('photo_urls')->nullable()->after('geofence_verified');
        });
    }

    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->dropColumn(['coordinates', 'geofence_verified', 'photo_urls']);
        });
    }
};

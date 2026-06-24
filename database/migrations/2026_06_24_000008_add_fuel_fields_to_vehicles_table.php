<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('fuel_type', ['diesel', 'petrol', 'electric', 'hybrid'])->nullable()->after('capacity_unit');
            $table->decimal('tank_capacity', 10, 2)->nullable()->after('fuel_type');
            $table->decimal('fuel_consumption_rate', 8, 2)->nullable()->comment('L/100km')->after('tank_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['fuel_type', 'tank_capacity', 'fuel_consumption_rate']);
        });
    }
};

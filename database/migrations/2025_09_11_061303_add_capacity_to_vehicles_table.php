<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('capacity', 8, 2)->nullable()->after('color'); // e.g. 10.50
            $table->enum('capacity_unit', ['kg', 'tons'])->default('tons')->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'capacity_unit']);
        });
    }
};

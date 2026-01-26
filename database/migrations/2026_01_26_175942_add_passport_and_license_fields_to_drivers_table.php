<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->date('licence_expiry')->nullable()->after('driving_licence');
            $table->string('licence_file')->nullable()->after('licence_expiry');
            $table->date('passport_expiry')->nullable()->after('passport_number');
            $table->string('passport_file')->nullable()->after('passport_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();   // ISO 4217 code
            $table->string('name');               // e.g. US Dollar
            $table->string('symbol', 5)->nullable(); // $, €, Fr
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Adjust exchange_rates to reference currencies
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->foreignId('base_currency_id')->nullable()->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('target_currency_id')->nullable()->constrained('currencies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('base_currency_id');
            $table->dropConstrainedForeignId('target_currency_id');
        });

        Schema::dropIfExists('currencies');
    }
};

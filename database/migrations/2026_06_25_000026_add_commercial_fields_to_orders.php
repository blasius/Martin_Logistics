<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'contract_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('contract_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
                $table->foreignId('currency_id')->nullable()->after('price')->constrained()->nullOnDelete();
                $table->decimal('weight_kg', 10, 2)->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['contract_id', 'currency_id', 'weight_kg']);
        });
    }
};

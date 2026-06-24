<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expense_types', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('category')->nullable()->after('description');
            $table->string('expense_class')->default('fixed')->after('category');
            $table->decimal('default_amount', 15, 2)->nullable()->after('expense_class');
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete()->after('default_amount');
            $table->string('status', 20)->default('active')->after('currency_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('expense_types', function (Blueprint $table) {
            $table->string('name')->unique()->change();
            $table->dropColumn([
                'category', 'expense_class', 'default_amount',
                'currency_id', 'status', 'created_by',
                'approved_by', 'approved_at',
            ]);
        });
    }
};

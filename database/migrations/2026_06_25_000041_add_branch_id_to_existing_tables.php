<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('code')->constrained()->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });

        Schema::table('fuel_tanks', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('fuel_type')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('drivers', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('orders', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('invoices', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('warehouses', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('employees', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('fuel_tanks', fn(Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('guard_name');
            $table->boolean('is_super_admin')->default(false)->after('description');
            $table->boolean('is_active')->default(true)->after('is_super_admin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('group')->nullable()->after('slug');
            $table->text('description')->nullable()->after('group');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete()->after('model_id');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description', 'is_super_admin', 'is_active', 'created_by']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['slug', 'group', 'description']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn(['assigned_by', 'assigned_at']);
        });
    }
};

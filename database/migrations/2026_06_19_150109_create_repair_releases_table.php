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
        Schema::create('repair_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('released_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->decimal('odometer_at_release', 12, 2)->nullable();
            $table->text('unresolved_issues')->nullable()->comment('Mandatory note of any remaining problems');
            $table->boolean('checklist_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_releases');
    }
};

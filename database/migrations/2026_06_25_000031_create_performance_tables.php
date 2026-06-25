<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_submissions', function (Blueprint $table) {
            $table->id();
            $table->morphs('rateable', 'rs_rateable_index');
            $table->tinyInteger('rating')->unsigned();
            $table->string('category');
            $table->text('comment')->nullable();
            $table->foreignId('rater_id')->constrained('users');
            $table->nullableMorphs('submission_context', 'rs_submission_context_index');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->morphs('scoreable', 'ps_scoreable_index');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->decimal('fuel_efficiency_score', 5, 2)->default(0);
            $table->decimal('on_time_delivery_score', 5, 2)->default(0);
            $table->decimal('route_compliance_score', 5, 2)->default(0);
            $table->decimal('expense_management_score', 5, 2)->default(0);
            $table->decimal('safety_score', 5, 2)->default(0);
            $table->decimal('human_rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('human_rating_count')->default(0);
            $table->decimal('automated_score', 5, 2)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['scoreable_type', 'scoreable_id', 'period_start', 'period_end'], 'perf_score_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_scores');
        Schema::dropIfExists('rating_submissions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fine_checks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->index();
            $table->morphs('checkable'); // checkable_type, checkable_id
            $table->enum('result', ['clear','fined','error'])->default('clear');
            $table->integer('ticket_count')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->json('response')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_checks');
    }
};

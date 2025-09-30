<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->text('approval_notes')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('proof_file')->nullable();
            $table->json('signatures')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            //
        });
    }
};

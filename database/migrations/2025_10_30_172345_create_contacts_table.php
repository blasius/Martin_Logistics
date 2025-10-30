<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['email', 'phone', 'whatsapp', 'telegram', 'other']);
            $table->string('value');
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('verification_code')->nullable();
            $table->timestamp('code_expires_at')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

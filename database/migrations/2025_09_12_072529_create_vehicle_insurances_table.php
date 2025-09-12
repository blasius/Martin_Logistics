<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('policy_number');
            $table->string('provider_name')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('document_path'); // scanned insurance certificate
            $table->enum('status', ['active', 'expired', 'pending'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_insurances');
    }
};

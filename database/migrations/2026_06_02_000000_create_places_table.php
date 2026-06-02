<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('place_key')->unique()->index();
            $table->string('name');
            $table->string('type')->default('other');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('county')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->geometry('location', subtype: 'point', srid: 4326);
            $table->decimal('radius_meters', 10, 2)->default(50);
            $table->timestamps();
            $table->spatialIndex('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};

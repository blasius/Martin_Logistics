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
        // 1. Clean up from any previous failed runs
        Schema::dropIfExists('routes_new');

        // 2. Create the shadow table
        Schema::create('routes_new', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->json('path'); // Keep original JSON for UI

            // CRITICAL: Must be NOT NULL for Spatial Index
            $table->geometry('path_geometry', subtype: 'linestring', srid: 4326);

            $table->string('fleet_key')->nullable()->index();
            $table->decimal('allowed_deviation_meters', 8, 2)->default(500);
            $table->decimal('estimated_distance_km', 10, 2)->nullable();
            $table->timestamps();

            // 3. Add the spatial index
            $table->spatialIndex('path_geometry');
        });

        // 4. Transfer data
        // Note: Since path_geometry is NOT NULL, we provide a minimal
        // valid LineString for any records that might have empty paths
        DB::statement("
        INSERT INTO routes_new
        (id, name, path, path_geometry, allowed_deviation_meters, created_at, updated_at)
        SELECT
        id, name, path,
        ST_GeomFromText('LINESTRING(0 0, 0.0001 0.0001)', 4326),
        allowed_deviation_meters, created_at, updated_at
        FROM routes
    ");

        // 5. Swap
        Schema::drop('routes');
        Schema::rename('routes_new', 'routes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            //
        });
    }
};

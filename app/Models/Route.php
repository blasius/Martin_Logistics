<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fleet_key',
        'allowed_deviation_meters',
        'path',
        'path_geometry',
        'estimated_distance_km'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'path' => 'array', // This lets you access $route->path as a standard array
        'allowed_deviation_meters' => 'integer',
    ];

    /**
     * Hiding the geometry from standard JSON responses
     * prevents those binary/UTF-8 errors we saw earlier.
     */
    protected $hidden = [
        'path_geometry'
    ];

    /**
     * Optional: Helper to get the total distance of this route
     * directly from the database geometry.
     */
    public function getDistanceAttribute()
    {
        return \DB::selectOne("
            SELECT ST_Length(path_geometry) as distance
            FROM routes
            WHERE id = ?", [$this->id])->distance;
    }
}

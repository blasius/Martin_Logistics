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
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($route) {
            if (empty($route->fleet_key)) {
                $year = now()->year;

                // Get the last fleet_key for the current year
                $lastRoute = static::where('fleet_key', 'like', "RT-{$year}-%")
                    ->orderByDesc('id')
                    ->first();

                if ($lastRoute) {
                    // Extract the numeric part (e.g., from RT-2024-0005 get 5)
                    $parts = explode('-', $lastRoute->fleet_key);
                    $lastNumber = intval(end($parts));
                } else {
                    $lastNumber = 0;
                }

                $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                $route->fleet_key = "RT-{$year}-{$nextNumber}";
            }
        });
    }

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

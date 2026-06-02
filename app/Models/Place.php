<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_key',
        'name',
        'type',
        'description',
        'country',
        'county',
        'city',
        'address',
        'latitude',
        'longitude',
        'location',
        'radius_meters',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'float',
    ];

    protected $hidden = [
        'location',
    ];

    protected static function booted()
    {
        static::creating(function ($place) {
            if (empty($place->place_key)) {
                $year = now()->year;
                $lastPlace = static::where('place_key', 'like', "PL-{$year}-%")
                    ->orderByDesc('id')
                    ->first();

                if ($lastPlace) {
                    $parts = explode('-', $lastPlace->place_key);
                    $lastNumber = intval(end($parts));
                } else {
                    $lastNumber = 0;
                }

                $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                $place->place_key = "PL-{$year}-{$nextNumber}";
            }
        });
    }
}

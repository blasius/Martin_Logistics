<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class FuelCalibration extends Model
{
    protected $casts = [
        'calibration_table' => 'array', // JSON: { "raw": "liters" }
    ];

    /**
     * Helper to convert raw voltage/value to liters
     */
    public function convertToLiters($rawValue)
    {
        $table = $this->calibration_table;
        // Logic to interpolate between raw values in the JSON array
        // ...
        return $liters;
    }
}

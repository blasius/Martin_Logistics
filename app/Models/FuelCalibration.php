<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class FuelCalibration extends Model
{
    protected $fillable = ['vehicle_id', 'calibration_table'];

    protected $casts = [
        'calibration_table' => 'array',
        // Wialon format: [ { "x": raw_reading, "y": liters } ] or [ { "a": k, "b": offset } ]
    ];

    /**
     * Convert a raw sensor reading (voltage/liters) to liters using the vehicle's
     * calibration table. Supports:
     *  - a linear multiplier table: [ { "a": k, "b": offset } ]  -> liters = raw * a + b
     *  - an XY interpolation table: [ { "x": raw, "y": liters } ] -> linear interpolation
     *    between bracketing points, clamped at the table ends (mirrors WialonService).
     *  - no table: documented 1:1 fallback (raw is treated as liters already).
     */
    public function convertToLiters($rawValue)
    {
        $raw = (float) $rawValue;
        $table = $this->calibration_table;

        if (!is_array($table) || empty($table)) {
            // No calibration configured — fall back to treating the reading as liters.
            return $raw;
        }

        $rows = array_values($table);

        // CASE A: Linear multiplier.
        if (isset($rows[0]['a']) && isset($rows[0]['b'])) {
            return (float) round(max(0, ($raw * (float) $rows[0]['a']) + (float) $rows[0]['b']), 2);
        }

        // CASE B: XY interpolation table (non-linear tanks).
        $points = collect($rows)
            ->filter(fn ($row) => isset($row['x']) && isset($row['y']))
            ->map(fn ($row) => [(float) $row['x'], (float) $row['y']])
            ->values()
            ->sortBy(fn ($p) => $p[0])
            ->values();

        if ($points->isEmpty()) {
            return $raw;
        }

        $first = $points->first();
        $last = $points->last();

        if ($raw <= $first[0]) {
            return (float) round($first[1], 2);
        }
        if ($raw >= $last[0]) {
            return (float) round($last[1], 2);
        }

        foreach ($points as $i => $point) {
            $next = $points->get($i + 1);
            if (!$next || $point[0] >= $next[0]) {
                continue;
            }
            if ($raw >= $point[0] && $raw <= $next[0]) {
                $rangeX = $next[0] - $point[0];
                if ($rangeX == 0) {
                    return (float) round($point[1], 2);
                }
                $value = $point[1] + ($raw - $point[0]) * ($next[1] - $point[1]) / $rangeX;
                return (float) round(max(0, $value), 2);
            }
        }

        return (float) round($last[1], 2);
    }
}
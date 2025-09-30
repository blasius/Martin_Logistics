<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RegionLocatorService
{
    protected array $regions = [];

    public function __construct()
    {
        $path = storage_path('app/geo/filtered_countries.geojson');

        if (!file_exists($path)) {
            throw new \Exception("GeoJSON file not found at: $path");
        }

        $json = json_decode(file_get_contents($path), true);

        if (!$json || !isset($json['features'])) {
            throw new \Exception("Invalid GeoJSON structure in $path");
        }

        $this->regions = $json['features'];
        Log::info("Loaded " . count($this->regions) . " regions from GeoJSON");
    }

    /**
     * Get the region name for given coordinates.
     */
    public function getRegionForPoint(float $lat, float $lon): string
    {
        $point = [$lon, $lat]; // ✅ Swap order for GeoJSON [lon, lat]

        foreach ($this->regions as $feature) {
            $geometry = $feature['geometry'];
            $props = $feature['properties'];
            $regionName = $props['NAM_1'] ?? $props['NAM_0'] ?? 'Unknown';
            $countryName = $props['NAM_0'] ?? $props['NAM_1'] ?? 'Unknown';
            $regionName = $regionName.', '.$countryName;

            if ($geometry['type'] === 'Polygon') {
                foreach ($geometry['coordinates'] as $ring) {
                    if ($this->pointInPolygon($point, $ring)) {
                        return $regionName;
                    }
                }
            }

            if ($geometry['type'] === 'MultiPolygon') {
                foreach ($geometry['coordinates'] as $poly) {
                    foreach ($poly as $ring) {
                        if ($this->pointInPolygon($point, $ring)) {
                            return $regionName;
                        }
                    }
                }
            }

            // Compute bounding box
            $coords = collect($geometry['coordinates'][0])->map(fn($c) => ['lon'=>$c[0],'lat'=>$c[1]]);
            $minLat = $coords->min('lat');
            $maxLat = $coords->max('lat');
            $minLon = $coords->min('lon');
            $maxLon = $coords->max('lon');

            //Log::info("Region $regionName bounds: lat[$minLat,$maxLat] lon[$minLon,$maxLon]");
            //Log::info("Vehicle: lat {$lat}, lon {$lon}");
        }

        return 'Unknown';
    }

    /**
     * Classic ray-casting point in polygon algorithm.
     */
    protected function pointInPolygon(array $point, array $polygon): bool
    {
        [$x, $y] = $point; // lon, lat
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            [$xi, $yi] = $polygon[$i];
            [$xj, $yj] = $polygon[$j];

            $intersect = (($yi > $y) != ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-10) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }
}

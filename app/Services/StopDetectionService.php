<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripHistory;
use App\Models\TripStop;
use App\Models\SupportTicket;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StopDetectionService
{
    private ?Collection $geofences = null;
    private array $routeCache = [];

    public function __construct(
        protected SupportAutoTicketService $autoTicket
    ) {}

    /**
     * Run dwell / stop monitoring for one vehicle's latest telemetry update.
     * Called from inside WialonService::syncTelemetry per-vehicle transaction.
     */
    public function process(
        int $vehicleId,
        float $latitude,
        float $longitude,
        float $speed,
        CarbonInterface $recordedAt
    ): void {
        $isStationary = $speed <= (float) config('stops.stationary_speed_kph', 2);

        $trip = Trip::query()
            ->where('vehicle_id', $vehicleId)
            ->active()
            ->latest('id')
            ->first();

        if (!$trip) return;

        if ($isStationary) {
            $this->trackStationary($trip, $latitude, $longitude, $recordedAt);
        } else {
            $this->closeWindow($trip, $recordedAt);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Stationary handling                                                */
    /* ------------------------------------------------------------------ */

    private function trackStationary(Trip $trip, float $lat, float $lon, CarbonInterface $at): void
    {
        if ($trip->stop_started_at === null) {
            $trip->updateQuietly([
                'stop_started_at' => $at,
                'stop_latitude'   => $lat,
                'stop_longitude'  => $lon,
            ]);

            TripHistory::create([
                'trip_id'  => $trip->id,
                'user_id'  => $trip->dispatcher_id ?? $trip->created_by,
                'action'   => 'stop_started',
                'changes'  => [
                    'started_at' => $at->toDateTimeString(),
                    'latitude'   => $lat,
                    'longitude'  => $lon,
                ],
            ]);
            return;
        }

        $durationMinutes = (int) max(0, $trip->stop_started_at->diffInMinutes($at));
        $classification  = $this->classifyStop($trip, $lat, $lon);

        if ($classification['expected']) return;

        $this->alertIfDue($trip, $durationMinutes, $classification, $at);
    }

    /* ------------------------------------------------------------------ */
    /*  Movement — close open stop window                                  */
    /* ------------------------------------------------------------------ */

    private function closeWindow(Trip $trip, CarbonInterface $at): void
    {
        if ($trip->stop_started_at === null) return;

        $startedAt       = $trip->stop_started_at;
        $durationMinutes = (int) max(0, $startedAt->diffInMinutes($at));
        $lat             = (float) ($trip->stop_latitude ?? 0);
        $lon             = (float) ($trip->stop_longitude ?? 0);
        $classification  = $this->classifyStop($trip, $lat, $lon);

        TripStop::create([
            'trip_id'                    => $trip->id,
            'vehicle_id'                 => $trip->vehicle_id,
            'latitude'                   => $trip->stop_latitude,
            'longitude'                  => $trip->stop_longitude,
            'started_at'                 => $startedAt,
            'ended_at'                   => $at,
            'duration_minutes'           => $durationMinutes,
            'classification'             => $classification['expected'] ? 'expected' : 'unexpected',
            'reason'                     => $classification['expected'] ? $classification['reason'] : 'unexpected',
            'is_off_corridor'            => $classification['off_corridor'],
            'distance_from_route_meters' => $classification['distance'],
            'alert_sent_at'              => $trip->stop_alerted_at,
            'alert_count'                => $trip->stop_alerted_at ? 1 : 0,
        ]);

        $trip->updateQuietly([
            'total_rest_minutes'      => ($trip->total_rest_minutes ?? 0) + $durationMinutes,
            'unexpected_stop_count'   => ($trip->unexpected_stop_count ?? 0) + ($classification['expected'] ? 0 : 1),
            'stop_started_at'         => null,
            'stop_latitude'           => null,
            'stop_longitude'          => null,
            'stop_alerted_at'         => null,
        ]);

        TripHistory::create([
            'trip_id' => $trip->id,
            'user_id' => $trip->dispatcher_id ?? $trip->created_by,
            'action'  => 'stop_ended',
            'changes' => [
                'started_at'       => $startedAt?->toDateTimeString(),
                'ended_at'         => $at->toDateTimeString(),
                'duration_minutes' => $durationMinutes,
                'classification'   => $classification['expected'] ? 'expected' : 'unexpected',
                'reason'           => $classification['expected'] ? $classification['reason'] : 'unexpected',
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Dispatcher alert logic                                             */
    /* ------------------------------------------------------------------ */

    private function alertIfDue(
        Trip $trip,
        int $durationMinutes,
        array $classification,
        CarbonInterface $at
    ): void {
        $unexpectedThreshold = (int) config('stops.unexpected_alert_minutes', 30);
        $offCorridorThreshold = (int) config('stops.off_corridor_alert_minutes', 5);
        $cooldown            = (int) config('stops.realert_cooldown_minutes', 30);

        $due = $durationMinutes >= $unexpectedThreshold
            || ($classification['off_corridor'] && $durationMinutes >= $offCorridorThreshold);

        if (!$due) return;

        if ($trip->stop_alerted_at && $at->diffInMinutes($trip->stop_alerted_at) < $cooldown) return;

        $priority = match (true) {
            $durationMinutes >= 120 => SupportTicket::PRIORITY_URGENT,
            $durationMinutes >= 60  => SupportTicket::PRIORITY_HIGH,
            default                  => SupportTicket::PRIORITY_NORMAL,
        };

        $plate   = $trip->vehicle?->plate_number ?? 'N/A';
        $driver  = $trip->driver?->user?->name ?? 'N/A';
        $when    = $trip->stop_started_at?->format('Y-m-d H:i') ?? $at->format('Y-m-d H:i');
        $reason  = $classification['off_corridor']
            ? 'stationary off-corridor (' . number_format((float) $classification['distance'], 0) . 'm from route)'
            : 'unexpectedly idle';

        $title = "Unexpected Stop — Trip #{$trip->id}";

        $description = sprintf(
            'Trip #%d (%s, driver: %s) has been %s since %s.',
            $trip->id,
            $plate,
            $driver,
            $reason,
            $when
        );

        $ticket = $this->autoTicket->open(
            [
                'title'       => $title,
                'description' => $description,
                'priority'    => $priority,
                'source'      => config('stops.source', 'auto_unexpected_stop'),
            ],
            $trip,
            config('stops.support_category', 'Unexpected Stop'),
            $trip->dispatcher_id
        );

        $trip->updateQuietly(['stop_alerted_at' => $at]);

        TripHistory::create([
            'trip_id' => $trip->id,
            'user_id' => $trip->dispatcher_id ?? $trip->created_by,
            'action'  => 'stop_alerted',
            'changes' => [
                'ticket_id'       => $ticket->id,
                'duration_minutes'=> $durationMinutes,
                'off_corridor'    => $classification['off_corridor'],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Stop classification                                               */
    /* ------------------------------------------------------------------ */

    private function classifyStop(Trip $trip, float $lat, float $lon): array
    {
        $route = $trip->route_id ? $this->routeFor($trip) : null;

        $distance    = null;
        $offCorridor = false;

        if ($route && is_array($route->path) && count($route->path) >= 2) {
            $distance = $this->distanceToPath($lat, $lon, $route->path);
            $offCorridor = $distance > (float) ($route->allowed_deviation_meters ?? 500);
        }

        if ($this->nearPlannedLocations($trip, $lat, $lon)) {
            return [
                'expected'    => true,
                'reason'      => 'delivery',
                'off_corridor'=> $offCorridor,
                'distance'    => $distance === null ? null : round($distance, 2),
            ];
        }

        if ($this->insideGeofence($lat, $lon)) {
            return [
                'expected'    => true,
                'reason'      => 'yard',
                'off_corridor'=> $offCorridor,
                'distance'    => $distance === null ? null : round($distance, 2),
            ];
        }

        return [
            'expected'    => false,
            'reason'      => 'unexpected',
            'off_corridor'=> $offCorridor,
            'distance'    => $distance === null ? null : round($distance, 2),
        ];
    }

    private function nearPlannedLocations(Trip $trip, float $lat, float $lon): bool
    {
        $route = $trip->route_id ? $this->routeFor($trip) : null;
        if (!$route || empty($route->path) || !is_array($route->path)) return false;

        $points = $route->path;
        $last   = $points[count($points) - 1];
        $first  = $points[0];
        $radius = (float) config('stops.expected_stop_radius_meters', 200);

        foreach ([$first, $last] as $point) {
            $plat = (float) ($point['lat'] ?? $point[0] ?? null);
            $plon = (float) ($point['lng'] ?? $point['lon'] ?? $point[1] ?? null);
            if ($plat === 0.0 && $plon === 0.0) continue;
            if ($this->distanceMeters($lat, $lon, $plat, $plon) <= $radius) return true;
        }

        return false;
    }

    private function insideGeofence(float $lat, float $lon): bool
    {
        foreach ($this->geofences() as $gf) {
            $polygon = $gf->polygon ?? null;
            if (is_string($polygon)) $polygon = json_decode($polygon, true);
            if (!is_array($polygon) || count($polygon) < 3) continue;
            if ($this->pointInPolygon($lat, $lon, $polygon)) return true;
        }
        return false;
    }

    /* ------------------------------------------------------------------ */
    /*  Geo helpers                                                        */
    /* ------------------------------------------------------------------ */

    private function routeFor(Trip $trip): object
    {
        return $this->routeCache[$trip->route_id] ??= \App\Models\Route::find($trip->route_id);
    }

    private function geofences(): Collection
    {
        return $this->geofences ??= DB::table('geofences')->get();
    }

    private function distanceToPath(float $lat, float $lon, array $path): float
    {
        $min = PHP_FLOAT_MAX;
        for ($i = 0; $i < count($path) - 1; $i++) {
            $a = $this->normalizePoint($path[$i]);
            $b = $this->normalizePoint($path[$i + 1]);
            $d = $this->crossTrackDistance($lat, $lon, $a[0], $a[1], $b[0], $b[1]);
            if ($d < $min) $min = $d;
        }
        return $min;
    }

    private function normalizePoint(mixed $point): array
    {
        if (is_array($point)) {
            $lat = (float) ($point['lat'] ?? $point['latitude'] ?? $point[0] ?? 0);
            $lon = (float) ($point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? $point[1] ?? 0);
            return [$lat, $lon];
        }
        return [0.0, 0.0];
    }

    private function crossTrackDistance(float $lat, float $lon, float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $latRad  = deg2rad($lat);
        $lonRad  = deg2rad($lon);
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $d13 = 2 * $earthRadius * asin(sqrt(
            sin(($latRad - $lat1Rad) / 2) ** 2 +
            cos($lat1Rad) * cos($latRad) * sin(($lonRad - $lon1Rad) / 2) ** 2
        ));

        $theta12 = atan2(
            sin($lon2Rad - $lon1Rad) * cos($lat2Rad),
            cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($lon2Rad - $lon1Rad)
        );

        $theta13 = atan2(
            sin($lonRad - $lon1Rad) * cos($latRad),
            cos($lat1Rad) * sin($latRad) - sin($lat1Rad) * cos($latRad) * cos($lonRad - $lon1Rad)
        );

        return abs(asin(sin($d13 / $earthRadius) * sin($theta13 - $theta12)) * $earthRadius);
    }

    private function distanceMeters(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $earthRadiusMeters = 6371000;
        $latDelta = deg2rad($toLat - $fromLat);
        $lonDelta = deg2rad($toLon - $fromLon);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lonDelta / 2) ** 2;
        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function pointInPolygon(float $lat, float $lon, array $polygon): bool
    {
        $inside = false;
        $count  = count($polygon);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = (float) ($polygon[$i]['lat'] ?? $polygon[$i][0]);
            $xi = (float) ($polygon[$i]['lng'] ?? $polygon[$i]['lon'] ?? $polygon[$i][1]);
            $yj = (float) ($polygon[$j]['lat'] ?? $polygon[$j][0]);
            $xj = (float) ($polygon[$j]['lng'] ?? $polygon[$j]['lon'] ?? $polygon[$j][1]);
            if (($yi > $lon) !== ($yj > $lon) && $lat < ($xj - $xi) * ($lon - $yi) / ($yj - $yi) + $xi) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
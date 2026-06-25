<?php

namespace App\Services;

use App\Models\RouteDeviationLog;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\Trip;
use App\Models\TripFuelAnalysis;
use App\Models\User;

class RouteIntelligenceService
{
    public function checkDeviation(Trip $trip): ?RouteDeviationLog
    {
        $snapshot = $trip->vehicle?->snapshot;
        if (!$snapshot || !$snapshot->latitude || !$snapshot->longitude) {
            return null;
        }

        $route = $trip->route;
        if (!$route || !$route->path || !is_array($route->path)) {
            return null;
        }

        $distance = $this->distanceToPath(
            (float) $snapshot->latitude,
            (float) $snapshot->longitude,
            $route->path
        );

        $allowedDeviation = (float) ($route->allowed_deviation_meters ?? 500);

        if ($distance <= $allowedDeviation) {
            $this->resolveDeviation($trip);
            return null;
        }

        $log = RouteDeviationLog::create([
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'latitude' => $snapshot->latitude,
            'longitude' => $snapshot->longitude,
            'distance_from_route_meters' => round($distance, 2),
            'detected_at' => now(),
        ]);

        $trip->updateQuietly([
            'is_deviated' => true,
            'deviation_detected_at' => $trip->deviation_detected_at ?? now(),
            'deviation_max_distance_meters' => max(
                $trip->deviation_max_distance_meters ?? 0,
                $distance
            ),
        ]);

        return $log;
    }

    public function createExcessiveFuelTicket(TripFuelAnalysis $analysis): SupportTicket
    {
        $category = SupportCategory::firstOrCreate(
            ['name' => 'Fuel Abuse'],
            [
                'description' => 'Auto-created tickets for excessive fuel consumption (>15% variance)',
                'is_active' => true,
            ]
        );

        $priority = match (true) {
            ($analysis->variance_percent ?? 0) > 30 => 'urgent',
            ($analysis->variance_percent ?? 0) > 20 => 'high',
            default => 'normal',
        };

        $plateNumber = $analysis->vehicle?->plate_number ?? 'N/A';
        $dispatcherId = $analysis->trip?->dispatcher_id;

        $ticket = SupportTicket::create([
            'user_id' => $analysis->trip?->created_by ?? 1,
            'support_category_id' => $category->id,
            'assigned_to' => $dispatcherId,
            'subject_type' => get_class($analysis),
            'subject_id' => $analysis->id,
            'title' => "Excessive Fuel — Trip #{$analysis->trip_id}",
            'description' => "Trip #{$analysis->trip_id} used {$analysis->fuel_used}L vs expected {$analysis->expected_consumption}L ({$analysis->variance_percent}% variance). Vehicle: {$plateNumber}.",
            'priority' => $priority,
            'source' => 'auto_fuel_flag',
        ]);

        if ($analysis->trip) {
            $analysis->trip->updateQuietly(['auto_ticket_id' => $ticket->id]);
        }

        return $ticket;
    }

    public function createDeviationTicket(RouteDeviationLog $log): SupportTicket
    {
        $trip = $log->trip;
        if (!$trip) {
            throw new \RuntimeException('Deviation log has no associated trip.');
        }

        if ($trip->auto_ticket_id) {
            $existing = SupportTicket::find($trip->auto_ticket_id);
            if ($existing) {
                return $existing;
            }
        }

        $category = SupportCategory::firstOrCreate(
            ['name' => 'Route Deviation'],
            [
                'description' => 'Auto-created tickets for route deviations detected by the intelligence engine',
                'is_active' => true,
            ]
        );

        $plateNumber = $trip->vehicle?->plate_number ?? 'N/A';
        $driverName = $trip->driver?->user?->name ?? 'N/A';
        $distanceMeters = $log->distance_from_route_meters ?? 0;

        $distanceKm = round($distanceMeters / 1000, 2);
        $priority = match (true) {
            $distanceKm > 10 => 'urgent',
            $distanceKm > 5  => 'high',
            default           => 'normal',
        };

        $dispatcherId = $trip->dispatcher_id;

        $ticket = SupportTicket::create([
            'user_id' => $trip->created_by ?? 1,
            'support_category_id' => $category->id,
            'assigned_to' => $dispatcherId,
            'subject_type' => get_class($log),
            'subject_id' => $log->id,
            'title' => "Route Deviation — Trip #{$trip->id}",
            'description' => "Trip #{$trip->id} ({$plateNumber}, driver: {$driverName}) deviated {$distanceKm}km from the planned route at {$log->detected_at?->format('Y-m-d H:i')}.",
            'priority' => $priority,
            'source' => 'auto_route_deviation',
        ]);

        $trip->updateQuietly(['auto_ticket_id' => $ticket->id]);

        $this->logTicketEvent($ticket, 'created', [
            'source' => 'auto_route_deviation',
            'deviation_log_id' => $log->id,
            'distance_meters' => $distanceMeters,
        ]);

        return $ticket;
    }

    public function dashboardStats(): array
    {
        $activeDeviations = Trip::where('is_deviated', true)
            ->whereIn('status', ['on_route', 'assigned'])
            ->count();

        $totalDeviationLogs = RouteDeviationLog::count();
        $unresolvedLogs = RouteDeviationLog::whereNull('resolved_at')->count();

        $excessiveTrips = TripFuelAnalysis::where('flag', 'excessive')
            ->whereNull('created_at')
            ->count();

        $recentDeviations = RouteDeviationLog::with('trip.vehicle', 'trip.driver.user')
            ->latest('detected_at')
            ->take(20)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'trip_id' => $l->trip_id,
                'vehicle_plate' => $l->trip?->vehicle?->plate_number,
                'driver_name' => $l->trip?->driver?->user?->name,
                'distance_meters' => $l->distance_from_route_meters,
                'detected_at' => $l->detected_at,
                'resolved_at' => $l->resolved_at,
            ]);

        $recentTickets = SupportTicket::whereHas('category', fn ($q) => $q->where('name', 'Fuel Abuse'))
            ->with('assignee')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'title' => $t->title,
                'priority' => $t->priority,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]);

        return [
            'active_deviations' => $activeDeviations,
            'total_deviation_logs' => $totalDeviationLogs,
            'unresolved_logs' => $unresolvedLogs,
            'excessive_fuel_trips' => $excessiveTrips,
            'recent_deviations' => $recentDeviations,
            'recent_auto_tickets' => $recentTickets,
        ];
    }

    private function logTicketEvent(SupportTicket $ticket, string $type, array $payload = []): void
    {
        $ticket->events()->create([
            'user_id' => auth()->id() ?? $ticket->user_id,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    private function distanceToPath(float $lat, float $lng, array $path): float
    {
        $minDistance = PHP_FLOAT_MAX;

        for ($i = 0; $i < count($path) - 1; $i++) {
            $p1 = $path[$i];
            $p2 = $path[$i + 1];
            $dist = $this->distanceToSegment($lat, $lng, $p1, $p2);
            if ($dist < $minDistance) {
                $minDistance = $dist;
            }
        }

        return $minDistance;
    }

    private function distanceToSegment(float $lat, float $lng, array $p1, array $p2): float
    {
        $p1Lat = (float) ($p1[0] ?? $p1['lat'] ?? $p1['latitude'] ?? 0);
        $p1Lng = (float) ($p1[1] ?? $p1['lng'] ?? $p1['longitude'] ?? 0);
        $p2Lat = (float) ($p2[0] ?? $p2['lat'] ?? $p2['latitude'] ?? 0);
        $p2Lng = (float) ($p2[1] ?? $p2['lng'] ?? $p2['longitude'] ?? 0);

        return $this->crossTrackDistance($lat, $lng, $p1Lat, $p1Lng, $p2Lat, $p2Lng);
    }

    private function crossTrackDistance(float $lat, float $lng, float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $d13 = 2 * $earthRadius * asin(sqrt(
            sin(($latRad - $lat1Rad) / 2) ** 2 +
            cos($lat1Rad) * cos($latRad) * sin(($lngRad - $lng1Rad) / 2) ** 2
        ));

        $theta12 = atan2(
            sin($lng2Rad - $lng1Rad) * cos($lat2Rad),
            cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($lng2Rad - $lng1Rad)
        );

        $theta13 = atan2(
            sin($lngRad - $lng1Rad) * cos($latRad),
            cos($lat1Rad) * sin($latRad) - sin($lat1Rad) * cos($latRad) * cos($lngRad - $lng1Rad)
        );

        $crossTrack = asin(sin($d13 / $earthRadius) * sin($theta13 - $theta12)) * $earthRadius;

        return abs($crossTrack);
    }

    private function resolveDeviation(Trip $trip): void
    {
        $activeLogs = RouteDeviationLog::where('trip_id', $trip->id)
            ->whereNull('resolved_at')
            ->get();

        $now = now();
        foreach ($activeLogs as $log) {
            $log->update([
                'resolved_at' => $now,
                'duration_minutes' => $now->diffInMinutes($log->detected_at),
            ]);
        }

        if ($trip->is_deviated) {
            $trip->updateQuietly([
                'is_deviated' => false,
                'deviation_duration_minutes' => $now->diffInMinutes(
                    $trip->deviation_detected_at ?? $now
                ),
            ]);
        }
    }
}

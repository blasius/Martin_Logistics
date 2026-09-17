<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Rest & Stop Monitoring
    |--------------------------------------------------------------------------
    | Thresholds used by StopDetectionService (run inside the telemetry pass).
    | All values are intentionally configurable from the back office / env so
    | behaviour never relies on hardcoded assumptions.
    */

    // A vehicle is considered stationary when moving at or below this speed (km/h).
    'stationary_speed_kph' => env('STOPS_STATIONARY_SPEED_KPH', 2),

    // A stop is "expected" when it falls within this radius (meters) of a planned
    // route endpoint (origin/destination) or inside a geofence polygon.
    'expected_stop_radius_meters' => env('STOPS_EXPECTED_RADIUS_METERS', 200),

    // An unexpected stop triggers a dispatcher alert after this many minutes.
    'unexpected_alert_minutes' => env('STOPS_UNEXPECTED_ALERT_MINUTES', 30),

    // A stop that is off-corridor (beyond the route's allowed deviation) triggers
    // an alert after this many minutes, regardless of location.
    'off_corridor_alert_minutes' => env('STOPS_OFF_CORRIDOR_ALERT_MINUTES', 5),

    // Minimum gap between alerts for the same open stop window.
    'realert_cooldown_minutes' => env('STOPS_REALERT_COOLDOWN_MINUTES', 30),

    'source' => 'auto_unexpected_stop',
    'support_category' => 'Unexpected Stop',
];
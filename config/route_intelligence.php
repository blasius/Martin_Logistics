<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Intelligence
    |--------------------------------------------------------------------------
    | Controls the route-deviation auto-ticketing pass that runs during each
    | telemetry sync (see WialonService::syncTelemetry).
    |
    */

    // Whether the telemetry pass should detect deviations and open tickets.
    'auto_ticket' => (bool) env('ROUTE_INTELLIGENCE_AUTO_TICKET', true),

    // Minimum hours between auto deviation tickets for the same trip. Keeps a
    // single excursion from flooding the support inbox; 0 disables the cooldown.
    'ticket_cooldown_hours' => (int) env('ROUTE_INTELLIGENCE_TICKET_COOLDOWN_HOURS', 6),
];

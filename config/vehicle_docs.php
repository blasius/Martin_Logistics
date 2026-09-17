<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vehicle / Driver Regulatory Documents
    |--------------------------------------------------------------------------
    | Maturity windows used by the mobile "My vehicle docs" endpoint and the
    | document expiry alerting command.
    |
    */

    // Documents expiring within this many days are flagged "expiring_soon".
    'window_days' => (int) env('VEHICLE_DOCS_WINDOW_DAYS', 30),

    // Documents expiring within this many days are considered critical.
    'critical_days' => (int) env('VEHICLE_DOCS_CRITICAL_DAYS', 7),
];

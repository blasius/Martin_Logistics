<?php

return [
    'sla' => [
        'low' => [
            'first_response_minutes' => 480, // 8h
            'resolution_minutes' => 2880,    // 48h
        ],
        'normal' => [
            'first_response_minutes' => 240, // 4h
            'resolution_minutes' => 1440,    // 24h
        ],
        'high' => [
            'first_response_minutes' => 60,  // 1h
            'resolution_minutes' => 480,     // 8h
        ],
        'urgent' => [
            'first_response_minutes' => 15,  // 15min
            'resolution_minutes' => 120,     // 2h
        ],
    ],
];

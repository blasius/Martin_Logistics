<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateCalculatorRequest
{
    public function __construct(
        public ?int $originPlaceId = null,
        public ?int $destinationPlaceId = null,
        public ?float $distanceKm = null,
        public ?float $weightKg = null,
        public ?string $vehicleType = null,
        public ?int $containerCount = null,
        public ?int $stopCount = null,
        public ?int $rateCardId = null,
        public ?int $clientId = null,
    ) {}
}

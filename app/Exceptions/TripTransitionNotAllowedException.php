<?php

namespace App\Exceptions;

use App\Models\Trip;
use RuntimeException;

class TripTransitionNotAllowedException extends RuntimeException
{
    public function __construct(Trip $trip, mixed $target, array $details = [])
    {
        $targetLabel = is_object($target) ? (string) $target->label : (string) $target;

        $message = sprintf(
            'Transition from status "%s" to "%s" is not allowed by the current trip flow configuration.',
            $trip->status,
            $targetLabel,
        );

        parent::__construct($message);
    }
}
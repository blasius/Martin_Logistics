<?php

namespace App\Rules;

use App\Models\DriverVehicleAssignment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueActiveAssignment implements ValidationRule
{
    protected string $type;
    protected ?int $ignoreId;

    public function __construct(string $type, ?int $ignoreId = null)
    {
        $this->type = $type; // "vehicle" or "driver"
        $this->ignoreId = $ignoreId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DriverVehicleAssignment::query()
            ->when($this->type === 'vehicle', fn ($q) => $q->where('vehicle_id', $value))
            ->when($this->type === 'driver', fn ($q) => $q->where('driver_id', $value))
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->when($this->ignoreId, fn ($q) => $q->where('id', '!=', $this->ignoreId));

        if ($query->exists()) {
            $fail("This {$this->type} already has an active assignment.");
        }
    }
}

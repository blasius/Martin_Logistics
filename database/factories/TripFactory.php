<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }
}

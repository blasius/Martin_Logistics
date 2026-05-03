<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'reference' => 'ORD-' . date('Y') . '-' . $this->faker->unique()->numerify('#####'),
            'origin' => $this->faker->city . ', ' . $this->faker->countryCode,
            'destination' => $this->faker->city . ', ' . $this->faker->countryCode,
            'pickup_date' => $this->faker->dateTimeBetween('+1 days', '+1 week'),
            'status' => 'pending',
            'price' => $this->faker->randomFloat(2, 500, 5000),
            'notes' => $this->faker->sentence,
        ];
    }
}

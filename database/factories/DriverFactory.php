<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => $this->faker->phoneNumber,
            'whatsapp_phone' => $this->faker->phoneNumber,
            'driving_licence' => $this->faker->unique()->numerify('DL-########'),
            'licence_expiry' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'passport_number' => $this->faker->unique()->numerify('P########'),
            'passport_expiry' => $this->faker->dateTimeBetween('+1 year', '+10 years'),
            'nationality' => $this->faker->country,
            'sex' => $this->faker->randomElement(['M', 'F']),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
        ];
    }
}

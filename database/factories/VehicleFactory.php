<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_number' => strtoupper($this->faker->bothify('??? ### ?')),
            'make' => $this->faker->randomElement(['Volvo', 'Mercedes', 'Scania', 'MAN', 'DAF']),
            'model' => $this->faker->word,
            'year' => $this->faker->numberBetween(2010, 2024),
            'color' => $this->faker->colorName,
            'status' => 'active',
            'capacity' => $this->faker->randomFloat(2, 10, 40),
            'capacity_unit' => 'Tons',
        ];
    }
}

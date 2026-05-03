<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['email', 'phone', 'whatsapp']),
            'value' => $this->faker->unique()->safeEmail,
            'is_primary' => true,
        ];
    }
}

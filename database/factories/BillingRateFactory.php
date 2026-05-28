<?php

namespace Database\Factories;

use App\Models\BillingRate;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRate>
 */
class BillingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rateable_type' => Client::class,
            'rateable_id' => Client::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 250),
            'label' => $this->faker->optional()->words(2, true),
        ];
    }
}

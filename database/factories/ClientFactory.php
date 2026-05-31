<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('######'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => ClientStatus::Active,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the client is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::Archived,
        ]);
    }
}

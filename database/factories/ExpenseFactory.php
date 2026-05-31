<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => null,
            'project_id' => null,
            'ticket_id' => null,
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 5, 2000),
            'incurred_on' => $this->faker->dateTimeBetween('-3 months')->format('Y-m-d'),
            'category' => $this->faker->optional()->word(),
            'is_billable' => true,
            'is_billed' => false,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the expense is billable to a client.
     */
    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => true,
        ]);
    }

    /**
     * Indicate that the expense has already been billed.
     */
    public function billed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => true,
            'is_billed' => true,
        ]);
    }
}

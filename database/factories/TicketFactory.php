<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'TK-'.$this->faker->unique()->numberBetween(1, 1_000_000),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
            'is_blocked' => false,
            'due_date' => null,
            'creator_id' => User::factory(),
            'sort_order' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $durationMinutes = $this->faker->numberBetween(15, 480);
        $startedAt = $this->faker->dateTimeBetween('-1 month');

        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'started_at' => $startedAt,
            'ended_at' => (clone $startedAt)->modify("+{$durationMinutes} minutes"),
            'duration_minutes' => $durationMinutes,
            'is_billable' => true,
            'is_billed' => false,
        ];
    }
}

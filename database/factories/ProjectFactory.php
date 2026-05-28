<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(3, true));
        $startsAt = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'client_id' => Client::factory(),
            'name' => $name,
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->optional()->paragraph(),
            'status' => ProjectStatus::Active,
            'budget' => $this->faker->optional()->randomFloat(2, 1000, 100000),
            'starts_at' => $startsAt,
            'ends_at' => $this->faker->optional()->dateTimeBetween($startsAt, '+6 months'),
        ];
    }

    /**
     * Indicate that the project is internal (not tied to a client).
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => null,
        ]);
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::OnHold,
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Completed,
        ]);
    }

    /**
     * Indicate that the project is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Archived,
        ]);
    }
}

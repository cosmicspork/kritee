<?php

namespace Database\Factories;

use App\Enums\RoadmapStatus;
use App\Models\Roadmap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Roadmap>
 */
class RoadmapFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => RoadmapStatus::Active,
            'is_public' => false,
        ];
    }

    /**
     * Indicate that the roadmap is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RoadmapStatus::Archived,
        ]);
    }

    /**
     * Indicate that the roadmap is publicly visible.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }
}

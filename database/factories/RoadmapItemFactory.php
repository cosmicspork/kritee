<?php

namespace Database\Factories;

use App\Enums\RoadmapItemStatus;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoadmapItem>
 */
class RoadmapItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'roadmap_id' => Roadmap::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => RoadmapItemStatus::Planned,
            'sort_order' => 0,
            'is_public' => false,
        ];
    }
}

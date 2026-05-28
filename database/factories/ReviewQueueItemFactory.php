<?php

namespace Database\Factories;

use App\Enums\ReviewQueueStatus;
use App\Enums\RiskLevel;
use App\Models\AgentExecution;
use App\Models\ReviewQueueItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewQueueItem>
 */
class ReviewQueueItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_execution_id' => AgentExecution::factory(),
            'action_type' => $this->faker->word(),
            'action_payload' => [],
            'description' => $this->faker->sentence(),
            'status' => ReviewQueueStatus::Pending,
            'risk_level' => RiskLevel::Low,
            'expires_at' => now()->addDay(),
        ];
    }
}

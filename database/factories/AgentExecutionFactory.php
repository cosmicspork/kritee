<?php

namespace Database\Factories;

use App\Enums\AgentExecutionStatus;
use App\Enums\AgentExecutionTriggerType;
use App\Models\AgentExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentExecution>
 */
class AgentExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_name' => $this->faker->word().'Agent',
            'trigger_type' => AgentExecutionTriggerType::Manual,
            'status' => AgentExecutionStatus::Pending,
        ];
    }
}

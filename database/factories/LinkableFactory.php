<?php

namespace Database\Factories;

use App\Enums\LinkRelationshipType;
use App\Models\Linkable;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Linkable>
 */
class LinkableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = Ticket::factory()->create();
        $target = Project::factory()->create();

        return [
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'relationship_type' => LinkRelationshipType::RelatesTo,
            'note' => null,
        ];
    }
}

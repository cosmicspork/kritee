<?php

namespace Database\Seeders;

use App\Enums\RoadmapItemStatus;
use App\Models\Client;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Illuminate\Database\Seeder;

class RoadmapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        $roadmaps = collect([
            Roadmap::factory()->public()->create(['client_id' => $clients->random()->getKey()]),
            Roadmap::factory()->public()->create(['client_id' => null]),
            Roadmap::factory()->create(['client_id' => $clients->random()->getKey()]),
            Roadmap::factory()->create(['client_id' => null]),
        ]);

        foreach ($roadmaps as $roadmap) {
            $order = 0;

            foreach (RoadmapItemStatus::cases() as $status) {
                RoadmapItem::factory()
                    ->count(random_int(1, 3))
                    ->for($roadmap)
                    ->sequence(fn () => [
                        'status' => $status,
                        'sort_order' => $order++,
                        'is_public' => $roadmap->is_public,
                    ])
                    ->create();
            }
        }
    }
}

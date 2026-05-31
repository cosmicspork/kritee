<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        Project::factory()->count(8)->recycle($clients)->create();
        Project::factory()->count(2)->onHold()->recycle($clients)->create();
        Project::factory()->count(2)->completed()->recycle($clients)->create();
        Project::factory()->count(2)->internal()->create();
    }
}

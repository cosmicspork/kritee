<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $projects = Project::whereNotNull('client_id')->get();

        if ($projects->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            $project = $projects->random();
            $factory = fake()->boolean(40) ? Expense::factory()->billed() : Expense::factory()->billable();

            $factory->create([
                'user_id' => $users->random()->getKey(),
                'client_id' => $project->client_id,
                'project_id' => $project->getKey(),
            ]);
        }
    }
}

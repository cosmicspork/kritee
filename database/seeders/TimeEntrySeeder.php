<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $tickets = Ticket::with('projects')->get();

        if ($tickets->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 60; $i++) {
            $ticket = $tickets->random();
            $user = $users->random();
            $billed = fake()->boolean(40);

            TimeEntry::factory()->create([
                'user_id' => $user->getKey(),
                'client_id' => $ticket->client_id,
                'project_id' => $ticket->projects->first()?->getKey(),
                'ticket_id' => $ticket->getKey(),
                'is_billable' => true,
                'is_billed' => $billed,
                'billed_rate' => $billed ? $user->default_hourly_rate : null,
            ]);
        }
    }
}

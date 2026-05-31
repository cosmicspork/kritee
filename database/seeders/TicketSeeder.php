<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        /** @var Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Project>> $projectsByClient */
        $projectsByClient = Project::whereNotNull('client_id')->get()->groupBy('client_id');

        foreach (TicketStatus::cases() as $status) {
            $tickets = Ticket::factory()
                ->count(8)
                ->recycle($users)
                ->sequence(fn (Sequence $sequence) => [
                    'status' => $status,
                    'sort_order' => $sequence->index,
                    'priority' => fake()->randomElement(TicketPriority::cases()),
                    'is_blocked' => $status === TicketStatus::Blocked,
                    'client_id' => $clients->random()->getKey(),
                    'assignee_id' => fake()->boolean(80) ? $users->random()->getKey() : null,
                    'due_date' => fake()->optional()->dateTimeBetween('-1 week', '+1 month'),
                ])
                ->create();

            foreach ($tickets as $ticket) {
                $clientProjects = $projectsByClient->get($ticket->client_id);

                if ($clientProjects !== null && $clientProjects->isNotEmpty()) {
                    $ticket->projects()->attach(
                        $clientProjects->random(random_int(1, min(2, $clientProjects->count())))
                    );
                }

                if (fake()->boolean(60)) {
                    Comment::factory()
                        ->count(random_int(1, 3))
                        ->for($ticket)
                        ->recycle($users)
                        ->create();
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $clients = Client::all();

        for ($i = 0; $i < 12; $i++) {
            // Roughly two-thirds of documents are tied to a client; the rest are
            // internal notes with no client association.
            $clientId = $clients->isNotEmpty() && fake()->boolean(65)
                ? $clients->random()->getKey()
                : null;

            Document::factory()->create([
                'uploaded_by' => $users->random()->getKey(),
                'client_id' => $clientId,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\BillingRate;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::factory()->count(6)->create()
            ->merge(Client::factory()->count(2)->archived()->create());

        foreach ($clients as $client) {
            Contact::factory()->for($client)->create(['is_primary' => true]);
            Contact::factory()->count(random_int(1, 2))->for($client)->create();

            BillingRate::factory()->create([
                'rateable_type' => $client->getMorphClass(),
                'rateable_id' => $client->getKey(),
                'label' => 'Standard rate',
            ]);
        }
    }
}

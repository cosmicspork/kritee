<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
            TicketSeeder::class,
            TimeEntrySeeder::class,
            ExpenseSeeder::class,
            InvoiceSeeder::class,
            RoadmapSeeder::class,
        ]);
    }
}

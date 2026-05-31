<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->admin()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'default_hourly_rate' => 150,
            ]);
        }

        User::factory()
            ->count(3)
            ->sequence(
                ['default_hourly_rate' => 95],
                ['default_hourly_rate' => 120],
                ['default_hourly_rate' => 140],
            )
            ->create();
    }
}

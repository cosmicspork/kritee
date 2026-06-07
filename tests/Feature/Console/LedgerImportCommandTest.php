<?php

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;

/**
 * @param  list<array<string, mixed>>  $rows
 */
function writeJsonl(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'ledger').'.jsonl';
    $lines = array_map(fn (array $r): string => (string) json_encode($r), $rows);
    file_put_contents($path, implode("\n", $lines)."\n");

    return $path;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cmdRow(array $overrides = []): array
{
    return array_merge([
        'date' => '2026-06-07',
        'vendor' => 'Laravel Cloud',
        'project' => 'personal',
        'amount' => 1.34,
        'currency' => 'USD',
    ], $overrides);
}

test('it imports a file as the sole user and exits 0', function () {
    User::factory()->create();
    Project::factory()->create(['slug' => 'personal']);

    $path = writeJsonl([cmdRow()]);

    $this->artisan('ledger:import', ['path' => $path])
        ->assertExitCode(0);

    expect(Expense::count())->toBe(1);
});

test('dry-run writes nothing', function () {
    User::factory()->create();
    Project::factory()->create(['slug' => 'personal']);

    $path = writeJsonl([cmdRow()]);

    $this->artisan('ledger:import', ['path' => $path, '--dry-run' => true])
        ->assertExitCode(0);

    expect(Expense::count())->toBe(0);
});

test('a row error makes the command exit 1', function () {
    User::factory()->create();
    // no project named "ghost" -> unresolved -> error

    $path = writeJsonl([cmdRow(['project' => 'ghost'])]);

    $this->artisan('ledger:import', ['path' => $path])
        ->assertExitCode(1);

    expect(Expense::count())->toBe(0);
});

test('an unreadable path exits 2', function () {
    User::factory()->create();

    $this->artisan('ledger:import', ['path' => '/no/such/file.jsonl'])
        ->assertExitCode(2);
});

test('ambiguous user without --user exits 2', function () {
    User::factory()->count(2)->create();
    Project::factory()->create(['slug' => 'personal']);

    $path = writeJsonl([cmdRow()]);

    $this->artisan('ledger:import', ['path' => $path])
        ->assertExitCode(2);

    expect(Expense::count())->toBe(0);
});

test('--user selects the recording user by email', function () {
    User::factory()->create();
    $target = User::factory()->create(['email' => 'me@example.test']);
    Project::factory()->create(['slug' => 'personal']);

    $path = writeJsonl([cmdRow()]);

    $this->artisan('ledger:import', ['path' => $path, '--user' => 'me@example.test'])
        ->assertExitCode(0);

    expect(Expense::query()->sole()->user_id)->toBe($target->getKey());
});

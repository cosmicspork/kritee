<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Ledger\ImportLedger;
use App\Actions\Ledger\ImportLedgerInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\LedgerRowStatus;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Services\Ledger\PlannedRow;

function importActAsUser(?User $user = null): User
{
    $user ??= User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ledgerRow(int $line, array $overrides = []): array
{
    return array_merge([
        '_line' => $line,
        'date' => '2026-06-07',
        'vendor' => 'Laravel Cloud',
        'project' => 'personal',
        'amount' => 1.34,
        'currency' => 'USD',
        'note' => 'cache 0.43; database 0.81',
    ], $overrides);
}

/**
 * @param  array<int, array<string, mixed>>  $rows
 */
function runImport(int $userId, array $rows, bool $dryRun = false): ActionResult
{
    return app(ImportLedger::class)->execute(ImportLedgerInput::from([
        'user_id' => $userId,
        'rows' => $rows,
        'dry_run' => $dryRun,
    ]));
}

test('it imports a row, resolving project and client and forcing non-billable', function () {
    $user = importActAsUser();
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->getKey(), 'slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1)]);

    expect($result->success)->toBeTrue()
        ->and($result->data['imported'])->toBe(1);

    $expense = Expense::query()->sole();
    expect($expense->vendor)->toBe('Laravel Cloud')
        ->and($expense->description)->toBe('Laravel Cloud')
        ->and((int) $expense->project_id)->toBe($project->getKey())
        ->and((int) $expense->client_id)->toBe($client->getKey())
        ->and((bool) $expense->is_billable)->toBeFalse()
        ->and($expense->notes)->toBe('cache 0.43; database 0.81')
        ->and($expense->ref)->not->toBeNull();
});

test('re-importing the same row dedups against the persisted ref', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $first = runImport($user->getKey(), [ledgerRow(1)]);
    $second = runImport($user->getKey(), [ledgerRow(1)]);

    expect($first->data['imported'])->toBe(1)
        ->and($second->data['imported'])->toBe(0)
        ->and(Expense::count())->toBe(1);

    $statuses = collect($second->data['planned'])->map(fn (PlannedRow $r) => $r->status);
    expect($statuses)->toContain(LedgerRowStatus::Duplicate);
});

test('duplicate rows within one file are imported once', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1), ledgerRow(2)]);

    expect($result->data['imported'])->toBe(1)
        ->and(Expense::count())->toBe(1);
});

test('dry-run reports the plan but writes nothing', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1)], dryRun: true);

    expect($result->data['imported'])->toBe(0)
        ->and(Expense::count())->toBe(0);

    $statuses = collect($result->data['planned'])->map(fn (PlannedRow $r) => $r->status);
    expect($statuses)->toContain(LedgerRowStatus::Import);
});

test('zero and negative amounts are skipped, not imported', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [
        ledgerRow(1, ['amount' => 0]),
        ledgerRow(2, ['amount' => -0.94]),
    ]);

    expect($result->data['imported'])->toBe(0)
        ->and(Expense::count())->toBe(0);

    $statuses = collect($result->data['planned'])->map(fn (PlannedRow $r) => $r->status->value)->all();
    expect($statuses)->toBe([LedgerRowStatus::SkipZero->value, LedgerRowStatus::SkipNegative->value]);
});

test('an unknown project slug is an error, not an auto-created project', function () {
    $user = importActAsUser();

    $result = runImport($user->getKey(), [ledgerRow(1, ['project' => 'ghost'])]);

    expect($result->data['imported'])->toBe(0)
        ->and(Project::count())->toBe(0);

    $row = $result->data['planned'][0];
    expect($row->status)->toBe(LedgerRowStatus::Error)
        ->and($row->message)->toContain('ghost');
});

test('a non-USD currency is rejected', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1, ['currency' => 'EUR'])]);

    expect($result->data['planned'][0]->status)->toBe(LedgerRowStatus::Error)
        ->and(Expense::count())->toBe(0);
});

test('a carried ref that disagrees with the recomputed one is flagged as drift', function () {
    $user = importActAsUser();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1, ['ref' => 'deadbeef'])], dryRun: true);

    expect($result->data['planned'][0]->drift)->toBeTrue();
});

test('import must run as a user', function () {
    app()->instance(Actor::class, new SystemActor);
    $user = User::factory()->create();
    Project::factory()->create(['slug' => 'personal']);

    $result = runImport($user->getKey(), [ledgerRow(1)]);

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and(Expense::count())->toBe(0);
});

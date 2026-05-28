<?php

use App\Models\BillingRate;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Billing\BillingRateCascade;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

function rateFor(Model $rateable, float $amount, ?string $from = null, ?string $to = null): BillingRate
{
    return BillingRate::factory()->create([
        'rateable_type' => $rateable->getMorphClass(),
        'rateable_id' => $rateable->getKey(),
        'amount' => $amount,
        'effective_from' => $from,
        'effective_to' => $to,
    ]);
}

test('it falls back to the user default hourly rate when no rate applies', function () {
    $user = User::factory()->create(['default_hourly_rate' => 95.00]);
    $entry = TimeEntry::factory()->for($user)->create();

    $rate = app(BillingRateCascade::class)->resolve($entry);

    expect($rate)->toBe('95.00');
});

test('it returns null when no tier yields a rate and the user has no default', function () {
    $user = User::factory()->create(['default_hourly_rate' => null]);
    $entry = TimeEntry::factory()->for($user)->create();

    expect(app(BillingRateCascade::class)->resolve($entry))->toBeNull();
});

test('the client rate overrides the user default', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    rateFor($client, 120.00);

    $entry = TimeEntry::factory()->for($user)->create(['client_id' => $client->getKey()]);

    expect(app(BillingRateCascade::class)->resolve($entry))->toBe('120.00');
});

test('the project rate overrides the client rate', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();
    rateFor($client, 120.00);
    rateFor($project, 150.00);

    $entry = TimeEntry::factory()->for($user)->create([
        'client_id' => $client->getKey(),
        'project_id' => $project->getKey(),
    ]);

    expect(app(BillingRateCascade::class)->resolve($entry))->toBe('150.00');
});

test('the ticket rate overrides every lower tier', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();
    $ticket = Ticket::factory()->for($user, 'creator')->create(['client_id' => $client->getKey()]);

    rateFor($client, 120.00);
    rateFor($project, 150.00);
    rateFor($ticket, 200.00);

    $entry = TimeEntry::factory()->for($user)->create([
        'client_id' => $client->getKey(),
        'project_id' => $project->getKey(),
        'ticket_id' => $ticket->getKey(),
    ]);

    expect(app(BillingRateCascade::class)->resolve($entry))->toBe('200.00');
});

test('it resolves the project rate through the ticket when the entry has no direct project', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();
    $ticket = Ticket::factory()->for($user, 'creator')->create(['client_id' => $client->getKey()]);
    $ticket->projects()->attach($project);

    rateFor($project, 150.00);

    $entry = TimeEntry::factory()->for($user)->create(['ticket_id' => $ticket->getKey()]);

    expect(app(BillingRateCascade::class)->resolve($entry))->toBe('150.00');
});

test('it ignores rates outside their effective window', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    rateFor($client, 120.00, '2026-01-01', '2026-03-31');

    $entry = TimeEntry::factory()->for($user)->create(['client_id' => $client->getKey()]);

    $within = app(BillingRateCascade::class)->resolve($entry, CarbonImmutable::parse('2026-02-15'));
    $after = app(BillingRateCascade::class)->resolve($entry, CarbonImmutable::parse('2026-06-01'));

    expect($within)->toBe('120.00')
        ->and($after)->toBe('50.00');
});

test('it honours an open-ended effective window', function () {
    $user = User::factory()->create(['default_hourly_rate' => 50.00]);
    $client = Client::factory()->create();
    rateFor($client, 120.00, '2026-01-01', null);

    $entry = TimeEntry::factory()->for($user)->create(['client_id' => $client->getKey()]);

    $before = app(BillingRateCascade::class)->resolve($entry, CarbonImmutable::parse('2025-12-31'));
    $after = app(BillingRateCascade::class)->resolve($entry, CarbonImmutable::parse('2030-01-01'));

    expect($before)->toBe('50.00')
        ->and($after)->toBe('120.00');
});

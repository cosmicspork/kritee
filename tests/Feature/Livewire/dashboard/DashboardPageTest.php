<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\TimeEntry\StopTimer;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

function actOnDashboard(?User $user = null): User
{
    $user = actAsUser($user);

    test()->actingAs($user);

    return $user;
}

test('the page renders quiet states when nothing is happening', function () {
    actOnDashboard();

    Livewire::test('pages::dashboard.index')
        ->assertOk()
        ->assertSee('No timer running.')
        ->assertSee('Nothing needs attention.')
        ->assertSee('Unbilled work')
        ->assertSee('Logged this week');
});

test('a running timer is shown with its description', function () {
    $user = actOnDashboard();

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'description' => 'Reconciling the May ledger import',
        'started_at' => now()->subHour(),
        'ended_at' => null,
    ]);

    Livewire::test('pages::dashboard.index')
        ->assertSee('Reconciling the May ledger import')
        ->assertSee('Stop timer');
});

test('stopping the timer goes through the StopTimer action', function () {
    $user = actOnDashboard();

    $entry = TimeEntry::factory()->create([
        'user_id' => $user->id,
        'started_at' => now()->subHour(),
        'ended_at' => null,
    ]);

    $this->mock(StopTimer::class, function ($mock) use ($entry) {
        $mock->shouldReceive('execute')->once()->andReturn(ActionResult::success($entry));
    });

    Livewire::test('pages::dashboard.index')
        ->call('stopTimer')
        ->assertHasNoErrors();
});

test('unbilled work is valued through the billing rate cascade', function () {
    $user = actOnDashboard(User::factory()->create(['default_hourly_rate' => '100.00']));

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'duration_minutes' => 90,
        'is_billable' => true,
        'is_billed' => false,
    ]);

    Livewire::test('pages::dashboard.index')
        ->assertSee('$150.00');
});

test('overdue invoices and blocked tickets appear in the attention list', function () {
    actOnDashboard();

    $client = Client::factory()->create(['name' => 'Meridian Press']);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_number' => 'INV-2026-014',
        'status' => InvoiceStatus::Sent,
        'due_at' => now()->subDays(12),
        'total' => '860.00',
    ]);

    Ticket::factory()->create([
        'client_id' => $client->id,
        'title' => 'Dedupe re-imported ledger rows',
        'is_blocked' => true,
    ]);

    Livewire::test('pages::dashboard.index')
        ->assertSee('INV-2026-014')
        ->assertSee('Meridian Press')
        ->assertSee('Dedupe re-imported ledger rows')
        ->assertSee('Blocked')
        ->assertSee('Open invoice');
});

test('the week strip totals the minutes logged this week', function () {
    $user = actOnDashboard();

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'duration_minutes' => 510,
        'is_billable' => false,
    ]);

    Livewire::test('pages::dashboard.index')
        ->assertSee('8h 30m');
});

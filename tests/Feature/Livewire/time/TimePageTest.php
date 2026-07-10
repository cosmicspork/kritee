<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\TimeEntry\DeleteTimeEntry;
use App\Actions\TimeEntry\RecordManualTimeEntry;
use App\Actions\TimeEntry\StartTimer;
use App\Actions\TimeEntry\UpdateTimeEntry;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * Bind a UserActor (so actions resolve a user) and authenticate the guard (so
 * the component's own reads scope to that user).
 */
function actOnTimePage(?User $user = null): User
{
    $user = actAsUser($user);

    test()->actingAs($user);

    return $user;
}

test('the page renders for an authenticated user', function () {
    actOnTimePage();

    Livewire::test('pages::time.index')
        ->assertOk()
        ->assertSee('Timer')
        ->assertSee('Log time');
});

test('starting the timer invokes StartTimer and opens an entry', function () {
    $started = false;

    $this->mock(StartTimer::class, function ($mock) use (&$started) {
        $mock->shouldReceive('execute')->once()->andReturnUsing(function ($input) use (&$started) {
            $started = true;

            return ActionResult::success(new TimeEntry);
        });
    });

    actOnTimePage();

    Livewire::test('pages::time.index')
        ->set('timerDescription', 'Fixing a bug')
        ->call('startTimer')
        ->assertHasNoErrors();

    expect($started)->toBeTrue();
});

test('starting the timer through the real action persists a running entry', function () {
    $user = actOnTimePage();

    Livewire::test('pages::time.index')
        ->set('timerDescription', 'Investigating')
        ->set('timerIsBillable', false)
        ->call('startTimer')
        ->assertHasNoErrors()
        ->assertSee('Investigating')
        ->assertSee('Stop timer');

    $entry = TimeEntry::sole();

    expect($entry->user_id)->toBe($user->getKey())
        ->and($entry->ended_at)->toBeNull()
        ->and($entry->is_billable)->toBeFalse();
});

test('stopping the timer invokes StopTimer and closes the entry', function () {
    $user = actOnTimePage();

    $running = TimeEntry::factory()->for($user)->create([
        'started_at' => now()->subMinutes(30),
        'ended_at' => null,
        'duration_minutes' => 0,
    ]);

    Livewire::test('pages::time.index')
        ->call('stopTimer')
        ->assertHasNoErrors();

    expect($running->refresh()->ended_at)->not->toBeNull()
        ->and($running->duration_minutes)->toBeGreaterThan(0);
});

test('recording a manual entry persists the selected start and derived end time', function () {
    $user = actOnTimePage();

    Livewire::test('pages::time.index')
        ->set('manualStartedAt', '2026-05-21T09:30')
        ->set('manualDurationMinutes', 60)
        ->set('manualDescription', 'Client call')
        ->set('manualIsBillable', false)
        ->call('recordManualEntry')
        ->assertHasNoErrors()
        ->assertSee('Client call');

    $entry = TimeEntry::sole();

    expect($entry->user_id)->toBe($user->getKey())
        ->and($entry->started_at->toDateTimeString())->toBe('2026-05-21 09:30:00')
        ->and($entry->ended_at->toDateTimeString())->toBe('2026-05-21 10:30:00')
        ->and($entry->duration_minutes)->toBe(60)
        ->and($entry->is_billable)->toBeFalse();
});

test('a manual entry requires a start datetime', function () {
    actOnTimePage();

    $this->mock(RecordManualTimeEntry::class)->shouldNotReceive('execute');

    Livewire::test('pages::time.index')
        ->set('manualStartedAt', '')
        ->set('manualDurationMinutes', 30)
        ->set('manualDescription', 'Client call')
        ->call('recordManualEntry')
        ->assertHasErrors(['manualStartedAt' => ['required']]);

    expect(TimeEntry::count())->toBe(0);
});

test('a non-positive manual duration fails validation before the action runs', function () {
    actOnTimePage();

    $this->mock(RecordManualTimeEntry::class)->shouldNotReceive('execute');

    Livewire::test('pages::time.index')
        ->set('manualDurationMinutes', 0)
        ->call('recordManualEntry')
        ->assertHasErrors(['manualDurationMinutes']);

    expect(TimeEntry::count())->toBe(0);
});

test('editing an entry pre-fills the start datetime for a datetime-local input', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => '2026-05-21 09:30:00',
        'ended_at' => '2026-05-21 10:30:00',
        'duration_minutes' => 60,
        'description' => 'Original',
    ]);

    Livewire::test('pages::time.index')
        ->call('editEntry', $entry->getKey())
        ->assertSet('showEditModal', true)
        ->assertSet('editStartedAt', '2026-05-21T09:30')
        ->assertSet('editDurationMinutes', 60)
        ->assertSet('editDescription', 'Original');
});

test('editing a dateless entry pre-fills the start datetime from when it was created', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => null,
        'ended_at' => null,
        'duration_minutes' => 45,
        'created_at' => '2026-05-20 08:45:17',
        'updated_at' => '2026-05-20 09:00:00',
    ]);

    Livewire::test('pages::time.index')
        ->call('editEntry', $entry->getKey())
        ->assertSet('showEditModal', true)
        ->assertSet('editStartedAt', '2026-05-20T08:45')
        ->assertSet('editDurationMinutes', 45);
});

test('saving an edited entry persists the selected start and derived end time', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => '2026-05-21 09:00:00',
        'ended_at' => '2026-05-21 09:30:00',
        'duration_minutes' => 30,
        'description' => 'Original',
        'is_billable' => true,
    ]);

    Livewire::test('pages::time.index')
        ->call('editEntry', $entry->getKey())
        ->assertSet('showEditModal', true)
        ->set('editStartedAt', '2026-05-22T14:15')
        ->set('editDurationMinutes', 75)
        ->set('editDescription', 'Revised')
        ->set('editIsBillable', false)
        ->call('saveEntry')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $entry->refresh();

    expect($entry->started_at->toDateTimeString())->toBe('2026-05-22 14:15:00')
        ->and($entry->ended_at->toDateTimeString())->toBe('2026-05-22 15:30:00')
        ->and($entry->duration_minutes)->toBe(75)
        ->and($entry->description)->toBe('Revised')
        ->and($entry->is_billable)->toBeFalse();
});

test('saving an edited entry requires a start datetime', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => '2026-05-21 09:30:00',
        'ended_at' => '2026-05-21 10:00:00',
        'duration_minutes' => 30,
        'description' => 'Original',
    ]);

    $originalStartedAt = $entry->started_at->toDateTimeString();
    $originalEndedAt = $entry->ended_at->toDateTimeString();

    $this->mock(UpdateTimeEntry::class)->shouldNotReceive('execute');

    Livewire::test('pages::time.index')
        ->call('editEntry', $entry->getKey())
        ->set('editStartedAt', '')
        ->call('saveEntry')
        ->assertHasErrors(['editStartedAt' => ['required']]);

    $entry->refresh();

    expect($entry->started_at->toDateTimeString())->toBe($originalStartedAt)
        ->and($entry->ended_at->toDateTimeString())->toBe($originalEndedAt)
        ->and($entry->duration_minutes)->toBe(30)
        ->and($entry->description)->toBe('Original');
});

test('saving an edited entry preserves sub-minute start precision when the displayed start is unchanged', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => '2026-05-21 09:30:45',
        'ended_at' => '2026-05-21 10:00:45',
        'duration_minutes' => 30,
        'description' => 'Original',
    ]);

    Livewire::test('pages::time.index')
        ->call('editEntry', $entry->getKey())
        ->assertSet('editStartedAt', '2026-05-21T09:30')
        ->set('editDurationMinutes', 75)
        ->set('editDescription', 'Revised')
        ->call('saveEntry')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $entry->refresh();

    expect($entry->started_at->toDateTimeString())->toBe('2026-05-21 09:30:45')
        ->and($entry->ended_at->toDateTimeString())->toBe('2026-05-21 10:45:45')
        ->and($entry->duration_minutes)->toBe(75)
        ->and($entry->description)->toBe('Revised');
});

test('deleting an entry invokes DeleteTimeEntry', function () {
    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create();

    Livewire::test('pages::time.index')
        ->call('deleteEntry', $entry->getKey())
        ->assertHasNoErrors();

    expect(TimeEntry::find($entry->getKey()))->toBeNull();
});

test('a failed action surfaces its error and changes nothing', function () {
    $this->mock(DeleteTimeEntry::class, function ($mock) {
        $mock->shouldReceive('execute')->once()->andReturn(
            ActionResult::failure(['authorization' => 'Not authorized to delete this time entry.']),
        );
    });

    $user = actOnTimePage();

    $entry = TimeEntry::factory()->for($user)->create();

    Livewire::test('pages::time.index')
        ->call('deleteEntry', $entry->getKey())
        ->assertHasNoErrors();

    expect(TimeEntry::find($entry->getKey()))->not->toBeNull();
});

test('entries show their work date from started_at', function () {
    $user = actOnTimePage();

    $datedEntry = TimeEntry::factory()->for($user)->create([
        'description' => 'Discovery workshop',
        'started_at' => CarbonImmutable::parse('2026-05-28 09:15:00'),
        'ended_at' => CarbonImmutable::parse('2026-05-28 10:00:00'),
        'duration_minutes' => 45,
    ]);

    $manualEntry = TimeEntry::factory()->for($user)->create([
        'description' => 'Backfilled admin',
        'started_at' => null,
        'ended_at' => null,
        'duration_minutes' => 30,
    ]);

    Livewire::test('pages::time.index')
        ->assertSeeHtml('data-test="entry-date-'.$datedEntry->getKey().'">2026-05-28</td>')
        ->assertSeeHtml('data-test="entry-date-'.$manualEntry->getKey().'">—</td>');
});

test('a user only sees their own entries', function () {
    $user = actOnTimePage();
    $other = User::factory()->create();

    TimeEntry::factory()->for($user)->create(['description' => 'Mine']);
    TimeEntry::factory()->for($other)->create(['description' => 'Theirs']);

    Livewire::test('pages::time.index')
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

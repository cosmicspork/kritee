<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Expense\RecordExpense;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
    Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');
});

test('it records an expense through the action and redirects', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::expenses.create')
        ->set('amount', '42.50')
        ->set('incurredOn', '2026-05-01')
        ->set('description', 'Train fare')
        ->set('category', 'travel')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->getKey(),
        'amount' => '42.50',
        'description' => 'Train fare',
        'category' => 'travel',
    ]);
});

test('it invokes the RecordExpense action exactly once', function (): void {
    $user = User::factory()->create();

    $action = Mockery::mock(RecordExpense::class);
    $action->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success(Expense::factory()->for($user)->make()));

    app()->instance(RecordExpense::class, $action);

    Livewire::actingAs($user)
        ->test('pages::expenses.create')
        ->set('amount', '10.00')
        ->set('incurredOn', '2026-05-02')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('expenses.index'));
});

test('it validates required fields before calling the action', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::expenses.create')
        ->set('amount', '')
        ->set('incurredOn', '')
        ->call('save')
        ->assertHasErrors(['amount', 'incurredOn'])
        ->assertNoRedirect();

    $this->assertDatabaseEmpty('expenses');
});

test('it associates a selected client', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::expenses.create')
        ->set('amount', '15.00')
        ->set('incurredOn', '2026-05-03')
        ->set('clientId', $client->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->getKey(),
        'client_id' => $client->getKey(),
    ]);
});

test('it stores an uploaded receipt and persists it as an attachment', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('receipt.pdf', 64, 'application/pdf');

    Livewire::actingAs($user)
        ->test('pages::expenses.create')
        ->set('amount', '30.00')
        ->set('incurredOn', '2026-05-04')
        ->set('receipt', $file)
        ->call('save')
        ->assertHasNoErrors();

    $expense = Expense::query()->where('user_id', $user->getKey())->firstOrFail();
    $attachment = Attachment::query()
        ->where('attachable_type', $expense->getMorphClass())
        ->where('attachable_id', $expense->getKey())
        ->firstOrFail();

    expect($attachment->filename)->toBe('receipt.pdf');
    Storage::disk('public')->assertExists($attachment->path);
});

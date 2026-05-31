<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Expense\UpdateExpense;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
    Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');
});

test('it hydrates the form from the expense', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create([
        'amount' => '88.00',
        'description' => 'Original description',
    ]);

    Livewire::actingAs($user)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->assertSet('amount', '88.00')
        ->assertSet('description', 'Original description');
});

test('it updates an expense through the action and redirects', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create(['description' => 'Before']);

    Livewire::actingAs($user)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->set('description', 'After')
        ->set('amount', '99.99')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->getKey(),
        'description' => 'After',
        'amount' => '99.99',
    ]);
});

test('it invokes the UpdateExpense action exactly once', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create();

    $action = Mockery::mock(UpdateExpense::class);
    $action->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success($expense));

    app()->instance(UpdateExpense::class, $action);

    Livewire::actingAs($user)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->set('amount', '12.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('expenses.index'));
});

test('it validates required fields before calling the action', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->set('amount', '')
        ->call('save')
        ->assertHasErrors('amount')
        ->assertNoRedirect();
});

test('a member may not edit another users expense', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $expense = Expense::factory()->for($owner)->create();

    Livewire::actingAs($stranger)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->assertForbidden();
});

test('an admin may edit any expense', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $expense = Expense::factory()->for(User::factory()->create())->create();

    Livewire::actingAs($admin)
        ->test('pages::expenses.edit', ['expense' => $expense])
        ->set('amount', '5.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->getKey(),
        'amount' => '5.00',
    ]);
});

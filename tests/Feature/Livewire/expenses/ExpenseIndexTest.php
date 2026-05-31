<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Expense\DeleteExpense;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * The product routes are wired in a later step. Registering them here keeps the
 * page-level `route()` calls and redirects resolvable in isolation; the names
 * below are the contract the routing step must honour.
 */
beforeEach(function (): void {
    Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
    Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');
});

test('it lists the acting user expenses', function (): void {
    $user = User::factory()->create();
    Expense::factory()->for($user)->create(['description' => 'Conference ticket']);
    Expense::factory()->for(User::factory()->create())->create(['description' => 'Someone else lunch']);

    Livewire::actingAs($user)
        ->test('pages::expenses.index')
        ->assertSee('Conference ticket')
        ->assertDontSee('Someone else lunch');
});

test('it deletes an expense through the action layer', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create();

    $action = Mockery::mock(DeleteExpense::class);
    $action->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success(['expense_id' => $expense->getKey()]));

    app()->instance(DeleteExpense::class, $action);

    Livewire::actingAs($user)
        ->test('pages::expenses.index')
        ->call('delete', $expense->getKey())
        ->assertHasNoErrors();
});

test('a real deletion removes the row via the action', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create(['description' => 'To be removed']);

    Livewire::actingAs($user)
        ->test('pages::expenses.index')
        ->assertSee('To be removed')
        ->call('delete', $expense->getKey())
        ->assertDontSee('To be removed');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->getKey()]);
});

test('it surfaces an action failure without removing the expense', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create();

    $action = Mockery::mock(DeleteExpense::class);
    $action->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['authorization' => 'You may not delete this expense.']));

    app()->instance(DeleteExpense::class, $action);

    Livewire::actingAs($user)
        ->test('pages::expenses.index')
        ->call('delete', $expense->getKey());

    $this->assertDatabaseHas('expenses', ['id' => $expense->getKey()]);
});

<?php

use App\Actions\Expense\UpdateExpense;
use App\Actions\Expense\UpdateExpenseInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function updateActAs(User $user): User
{
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it updates only the supplied fields', function () {
    $user = updateActAs(User::factory()->create());
    $expense = Expense::factory()->create([
        'user_id' => $user->getKey(),
        'amount' => '10.00',
        'description' => 'Original',
        'category' => 'travel',
    ]);

    $result = app(UpdateExpense::class)->execute(UpdateExpenseInput::validateAndCreate([
        'expense_id' => $expense->getKey(),
        'amount' => '42.00',
    ]));

    expect($result->success)->toBeTrue()
        ->and($result->data->amount)->toBe('42.00')
        ->and($result->data->description)->toBe('Original')
        ->and($result->data->category)->toBe('travel');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->getKey(),
        'amount' => '42.00',
        'description' => 'Original',
    ]);
});

test('a user may not update another user\'s expense', function () {
    updateActAs(User::factory()->create());
    $expense = Expense::factory()->create([
        'user_id' => User::factory()->create()->getKey(),
        'amount' => '10.00',
    ]);

    $result = app(UpdateExpense::class)->execute(UpdateExpenseInput::validateAndCreate([
        'expense_id' => $expense->getKey(),
        'amount' => '99.00',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->getKey(),
        'amount' => '10.00',
    ]);
});

test('updating a missing expense fails', function () {
    updateActAs(User::factory()->create());

    $result = app(UpdateExpense::class)->execute(UpdateExpenseInput::validateAndCreate([
        'expense_id' => 99999,
        'amount' => '1.00',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('expense_id');
});

test('an invalid amount is rejected before any side effects', function () {
    updateActAs(User::factory()->create());

    expect(fn () => UpdateExpenseInput::validateAndCreate([
        'expense_id' => 1,
        'amount' => 'nope',
    ]))->toThrow(ValidationException::class);
});

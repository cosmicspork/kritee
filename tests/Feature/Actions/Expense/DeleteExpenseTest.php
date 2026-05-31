<?php

use App\Actions\Expense\DeleteExpense;
use App\Actions\Expense\DeleteExpenseInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;

function deleteActAs(User $user): User
{
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it deletes the expense and its receipts', function () {
    $user = deleteActAs(User::factory()->create());
    $expense = Expense::factory()->create(['user_id' => $user->getKey()]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => $expense->getMorphClass(),
        'attachable_id' => $expense->getKey(),
        'uploaded_by' => $user->getKey(),
    ]);

    $result = app(DeleteExpense::class)->execute(DeleteExpenseInput::validateAndCreate([
        'expense_id' => $expense->getKey(),
    ]));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBe(['expense_id' => $expense->getKey()]);

    $this->assertDatabaseMissing('expenses', ['id' => $expense->getKey()]);
    $this->assertDatabaseMissing('attachments', ['id' => $attachment->getKey()]);
});

test('a user may not delete another user\'s expense', function () {
    deleteActAs(User::factory()->create());
    $expense = Expense::factory()->create([
        'user_id' => User::factory()->create()->getKey(),
    ]);

    $result = app(DeleteExpense::class)->execute(DeleteExpenseInput::validateAndCreate([
        'expense_id' => $expense->getKey(),
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('expenses', ['id' => $expense->getKey()]);
});

test('deleting a missing expense fails', function () {
    deleteActAs(User::factory()->create());

    $result = app(DeleteExpense::class)->execute(DeleteExpenseInput::validateAndCreate([
        'expense_id' => 99999,
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('expense_id');
});

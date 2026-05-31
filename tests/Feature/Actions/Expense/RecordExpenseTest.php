<?php

use App\Actions\Expense\RecordExpense;
use App\Actions\Expense\RecordExpenseInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\UserRole;
use App\Events\ExpenseRecorded;
use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function recordActAsUser(?User $user = null): User
{
    $user ??= User::factory()->create();

    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it records an expense for the acting user', function () {
    $user = recordActAsUser();

    $result = app(RecordExpense::class)->execute(RecordExpenseInput::validateAndCreate([
        'user_id' => $user->getKey(),
        'amount' => '125.50',
        'incurred_on' => '2026-05-01',
        'description' => 'Train fare',
        'category' => 'travel',
    ]));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Expense::class)
        ->and($result->data->amount)->toBe('125.50')
        ->and($result->data->description)->toBe('Train fare');

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->getKey(),
        'amount' => '125.50',
        'category' => 'travel',
        'is_billable' => true,
    ]);
});

test('it persists an attached receipt as a morphed attachment', function () {
    $user = recordActAsUser();

    $result = app(RecordExpense::class)->execute(RecordExpenseInput::validateAndCreate([
        'user_id' => $user->getKey(),
        'amount' => '40.00',
        'incurred_on' => '2026-05-10',
        'receipt' => [
            'filename' => 'receipt.pdf',
            'path' => 'receipts/abc/receipt.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
        ],
    ]));

    expect($result->success)->toBeTrue();

    $attachment = Attachment::query()
        ->where('attachable_type', (new Expense)->getMorphClass())
        ->where('attachable_id', $result->data->getKey())
        ->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->filename)->toBe('receipt.pdf')
        ->and($attachment->uploaded_by)->toBe($user->getKey());
});

test('it dispatches ExpenseRecorded on success', function () {
    Event::fake();

    $user = recordActAsUser();

    app(RecordExpense::class)->execute(RecordExpenseInput::validateAndCreate([
        'user_id' => $user->getKey(),
        'amount' => '10.00',
        'incurred_on' => '2026-05-12',
    ]));

    Event::assertDispatched(ExpenseRecorded::class, function (ExpenseRecorded $event) use ($user) {
        return $event->expense instanceof Expense
            && $event->actorId === (string) $user->getKey();
    });
});

test('a non-user actor cannot record an expense', function () {
    app()->instance(Actor::class, new SystemActor);
    $user = User::factory()->create();

    $result = app(RecordExpense::class)->execute(RecordExpenseInput::validateAndCreate([
        'user_id' => $user->getKey(),
        'amount' => '10.00',
        'incurred_on' => '2026-05-12',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    $this->assertDatabaseEmpty('expenses');
});

test('it rejects an invalid amount before any side effects', function () {
    recordActAsUser();

    expect(fn () => RecordExpenseInput::validateAndCreate([
        'user_id' => 1,
        'amount' => 'not-a-number',
        'incurred_on' => '2026-05-12',
    ]))->toThrow(ValidationException::class);
});

test('a repeated idempotency key does not record a second expense', function () {
    $user = recordActAsUser();

    $input = fn () => RecordExpenseInput::validateAndCreate([
        'user_id' => $user->getKey(),
        'amount' => '99.00',
        'incurred_on' => '2026-05-15',
        'idempotency_key' => 'expense-import-7',
    ]);

    $first = app(RecordExpense::class)->execute($input());
    $second = app(RecordExpense::class)->execute($input());

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(Expense::count())->toBe(1);
});

test('an admin may record an expense', function () {
    $admin = recordActAsUser(User::factory()->create(['role' => UserRole::Admin]));

    $result = app(RecordExpense::class)->execute(RecordExpenseInput::validateAndCreate([
        'user_id' => $admin->getKey(),
        'amount' => '5.00',
        'incurred_on' => '2026-05-20',
    ]));

    expect($result->success)->toBeTrue();
});

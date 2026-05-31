<?php

use App\Actions\Invoice\AddLineItemsFromUnbilled;
use App\Actions\Invoice\AddLineItemsFromUnbilledInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\User;

test('it pulls unbilled billable work onto the draft and recomputes totals', function () {
    actingAsInvoiceUser();

    $client = Client::factory()->create();
    $author = User::factory()->create(['default_hourly_rate' => 100.00]);
    $invoice = Invoice::factory()->for($client)->create([
        'status' => InvoiceStatus::Draft,
        'subtotal' => '0.00',
        'total' => '0.00',
    ]);

    $entry = TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'duration_minutes' => 90,
        'is_billable' => true,
        'is_billed' => false,
        'billed_rate' => null,
    ]);

    $expense = Expense::factory()->create([
        'client_id' => $client->getKey(),
        'amount' => '40.00',
        'is_billable' => true,
        'is_billed' => false,
    ]);

    $result = app(AddLineItemsFromUnbilled::class)->execute(
        AddLineItemsFromUnbilledInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue();

    $invoice->refresh();
    $entry->refresh();
    $expense->refresh();

    expect($invoice->lineItems()->count())->toBe(2)
        ->and($entry->is_billed)->toBeTrue()
        ->and($entry->billed_rate)->toBe('100.00')
        ->and($expense->is_billed)->toBeTrue()
        ->and($invoice->subtotal)->toBe('190.00')
        ->and($invoice->total)->toBe('190.00');

    $timeLine = $invoice->lineItems()->where('amount', '150.00')->first();

    expect($timeLine)->not->toBeNull()
        ->and($timeLine->quantity)->toBe('1.50')
        ->and($timeLine->unit_price)->toBe('100.00');
});

test('time entries with no resolvable rate are left unbilled', function () {
    actingAsInvoiceUser();

    $client = Client::factory()->create();
    $author = User::factory()->create(['default_hourly_rate' => null]);
    $invoice = Invoice::factory()->for($client)->create(['status' => InvoiceStatus::Draft]);

    $entry = TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'duration_minutes' => 60,
        'is_billable' => true,
        'is_billed' => false,
    ]);

    $result = app(AddLineItemsFromUnbilled::class)->execute(
        AddLineItemsFromUnbilledInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue()
        ->and($invoice->fresh()->lineItems()->count())->toBe(0)
        ->and($entry->fresh()->is_billed)->toBeFalse();
});

test('non-billable and already-billed sources are skipped', function () {
    actingAsInvoiceUser();

    $client = Client::factory()->create();
    $author = User::factory()->create(['default_hourly_rate' => 100.00]);
    $invoice = Invoice::factory()->for($client)->create(['status' => InvoiceStatus::Draft]);

    TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'duration_minutes' => 60,
        'is_billable' => false,
        'is_billed' => false,
    ]);
    TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'duration_minutes' => 60,
        'is_billable' => true,
        'is_billed' => true,
    ]);

    $result = app(AddLineItemsFromUnbilled::class)->execute(
        AddLineItemsFromUnbilledInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue()
        ->and($invoice->fresh()->lineItems()->count())->toBe(0);
});

test('it refuses to bill an invoice that is not a draft', function () {
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $result = app(AddLineItemsFromUnbilled::class)->execute(
        AddLineItemsFromUnbilledInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('status');
});

test('a non-user actor cannot add line items', function () {
    app()->instance(Actor::class, new SystemActor);

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(AddLineItemsFromUnbilled::class)->execute(
        AddLineItemsFromUnbilledInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');
});

test('a repeated idempotency key bills the sources only once', function () {
    actingAsInvoiceUser();

    $client = Client::factory()->create();
    $author = User::factory()->create(['default_hourly_rate' => 100.00]);
    $invoice = Invoice::factory()->for($client)->create(['status' => InvoiceStatus::Draft]);

    TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'duration_minutes' => 60,
        'is_billable' => true,
        'is_billed' => false,
    ]);

    $payload = ['invoice_id' => $invoice->getKey(), 'idempotency_key' => 'bill-once'];

    app(AddLineItemsFromUnbilled::class)->execute(AddLineItemsFromUnbilledInput::from($payload));
    app(AddLineItemsFromUnbilled::class)->execute(AddLineItemsFromUnbilledInput::from($payload));

    expect($invoice->fresh()->lineItems()->count())->toBe(1);
});

<?php

use App\Actions\Invoice\SendInvoice;
use App\Actions\Invoice\SendInvoiceInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceSent;
use App\Models\Invoice;
use Illuminate\Support\Facades\Event;

test('it issues a draft and dispatches InvoiceSent', function () {
    Event::fake([InvoiceSent::class]);
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(SendInvoice::class)->execute(
        SendInvoiceInput::from(['invoice_id' => $invoice->getKey(), 'due_at' => '2026-07-01']),
    );

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(InvoiceStatus::Sent)
        ->and($result->data->issued_at)->not->toBeNull()
        ->and($result->data->due_at->toDateString())->toBe('2026-07-01');

    Event::assertDispatched(InvoiceSent::class, fn (InvoiceSent $event): bool => $event->invoice->is($result->data));
});

test('a non-draft invoice cannot be sent', function () {
    Event::fake([InvoiceSent::class]);
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    $result = app(SendInvoice::class)->execute(
        SendInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('status');

    Event::assertNotDispatched(InvoiceSent::class);
});

test('a non-user actor cannot send an invoice', function () {
    app()->instance(Actor::class, new SystemActor);

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(SendInvoice::class)->execute(
        SendInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');
});

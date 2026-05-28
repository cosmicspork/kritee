<?php

use App\Actions\Invoice\MarkInvoicePaid;
use App\Actions\Invoice\MarkInvoicePaidInput;
use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use Illuminate\Support\Facades\Event;

test('it settles a sent invoice and dispatches InvoicePaid', function () {
    Event::fake([InvoicePaid::class]);
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $result = app(MarkInvoicePaid::class)->execute(
        MarkInvoicePaidInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(InvoiceStatus::Paid)
        ->and($result->data->paid_at)->not->toBeNull();

    Event::assertDispatched(InvoicePaid::class, fn (InvoicePaid $event): bool => $event->invoice->is($result->data));
});

test('a draft invoice cannot be marked paid', function () {
    Event::fake([InvoicePaid::class]);
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(MarkInvoicePaid::class)->execute(
        MarkInvoicePaidInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('status');

    Event::assertNotDispatched(InvoicePaid::class);
});

test('a repeated idempotency key dispatches the paid event once', function () {
    Event::fake([InvoicePaid::class]);
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $payload = ['invoice_id' => $invoice->getKey(), 'idempotency_key' => 'pay-once'];

    app(MarkInvoicePaid::class)->execute(MarkInvoicePaidInput::from($payload));
    app(MarkInvoicePaid::class)->execute(MarkInvoicePaidInput::from($payload));

    Event::assertDispatchedTimes(InvoicePaid::class, 1);
});

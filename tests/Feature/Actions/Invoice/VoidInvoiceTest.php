<?php

use App\Actions\Invoice\VoidInvoice;
use App\Actions\Invoice\VoidInvoiceInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;

test('it voids a draft invoice', function () {
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(VoidInvoice::class)->execute(
        VoidInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(InvoiceStatus::Void);
});

test('it voids a sent invoice', function () {
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $result = app(VoidInvoice::class)->execute(
        VoidInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeTrue()
        ->and($result->data->status)->toBe(InvoiceStatus::Void);
});

test('a paid invoice cannot be voided', function () {
    actingAsInvoiceUser();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    $result = app(VoidInvoice::class)->execute(
        VoidInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('status');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('a non-user actor cannot void an invoice', function () {
    app()->instance(Actor::class, new SystemActor);

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $result = app(VoidInvoice::class)->execute(
        VoidInvoiceInput::from(['invoice_id' => $invoice->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');
});

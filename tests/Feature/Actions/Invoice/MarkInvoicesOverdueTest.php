<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Invoice\MarkInvoicesOverdue;
use App\Actions\Invoice\MarkInvoicesOverdueInput;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceMarkedOverdue;
use App\Models\Invoice;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('issued invoices past their due date become overdue as the system actor', function () {
    actAsSystem();
    Event::fake([InvoiceMarkedOverdue::class]);

    $sent = Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'due_at' => now()->subDays(3)]);
    $viewed = Invoice::factory()->create(['status' => InvoiceStatus::Viewed, 'due_at' => now()->subDay()]);
    $future = Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'due_at' => now()->addDay()]);
    $draft = Invoice::factory()->create(['status' => InvoiceStatus::Draft, 'due_at' => now()->subDays(3)]);
    $paid = Invoice::factory()->create(['status' => InvoiceStatus::Paid, 'due_at' => now()->subDays(3)]);

    $result = app(MarkInvoicesOverdue::class)->execute(new MarkInvoicesOverdueInput);

    expect($result->success)->toBeTrue()
        ->and($sent->refresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($viewed->refresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($future->refresh()->status)->toBe(InvoiceStatus::Sent)
        ->and($draft->refresh()->status)->toBe(InvoiceStatus::Draft)
        ->and($paid->refresh()->status)->toBe(InvoiceStatus::Paid);

    Event::assertDispatchedTimes(InvoiceMarkedOverdue::class, 2);
});

test('a user actor may run the sweep too', function () {
    actAsUser();

    $sent = Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'due_at' => now()->subDay()]);

    $result = app(MarkInvoicesOverdue::class)->execute(new MarkInvoicesOverdueInput);

    expect($result->success)->toBeTrue()
        ->and($sent->refresh()->status)->toBe(InvoiceStatus::Overdue);
});

test('mark invoices overdue short-circuits a repeated idempotency key', function () {
    actAsSystem();

    $key = (string) Str::uuid();

    $first = app(MarkInvoicesOverdue::class)
        ->execute(new MarkInvoicesOverdueInput(idempotencyKey: $key));

    $second = app(MarkInvoicesOverdue::class)
        ->execute(new MarkInvoicesOverdueInput(idempotencyKey: $key));

    expect($first)->toBeInstanceOf(ActionResult::class)
        ->and($second)->toEqual($first);
});

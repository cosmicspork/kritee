<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Invoice\AddLineItemsFromUnbilled;
use App\Actions\Invoice\AddLineItemsFromUnbilledInput;
use App\Actions\Invoice\MarkInvoicePaid;
use App\Actions\Invoice\SendInvoice;
use App\Actions\Invoice\VoidInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;
use Mockery\MockInterface;

beforeEach(fn () => registerInvoiceRoutes());

test('a draft shows pull and send controls but not mark-paid', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->assertSeeHtml('data-test="pull-unbilled-button"')
        ->assertSeeHtml('data-test="send-invoice-button"')
        ->assertSeeHtml('data-test="void-invoice-button"')
        ->assertDontSeeHtml('data-test="mark-paid-button"');
});

test('a sent invoice shows mark-paid and void but not send', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->assertSeeHtml('data-test="mark-paid-button"')
        ->assertSeeHtml('data-test="void-invoice-button"')
        ->assertDontSeeHtml('data-test="send-invoice-button"')
        ->assertDontSeeHtml('data-test="pull-unbilled-button"');
});

test('a paid invoice exposes no write controls', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->assertDontSeeHtml('data-test="send-invoice-button"')
        ->assertDontSeeHtml('data-test="mark-paid-button"')
        ->assertDontSeeHtml('data-test="void-invoice-button"')
        ->assertDontSeeHtml('data-test="pull-unbilled-button"');
});

test('pulling unbilled work runs the action and reflects the new line items and totals', function () {
    $user = actAsUser();
    $client = Client::factory()->create();
    $author = User::factory()->create(['default_hourly_rate' => 100.00]);
    $invoice = Invoice::factory()->for($client)->create([
        'status' => InvoiceStatus::Draft,
        'subtotal' => '0.00',
        'total' => '0.00',
    ]);

    TimeEntry::factory()->for($author)->create([
        'client_id' => $client->getKey(),
        'description' => 'Discovery call',
        'duration_minutes' => 60,
        'is_billable' => true,
        'is_billed' => false,
    ]);
    Expense::factory()->create([
        'client_id' => $client->getKey(),
        'description' => 'Domain renewal',
        'amount' => '40.00',
        'is_billable' => true,
        'is_billed' => false,
    ]);

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->assertSeeHtml('data-test="no-line-items"')
        ->call('pullUnbilled')
        ->assertSee('Discovery call')
        ->assertSee('Domain renewal')
        ->assertSeeHtml('data-test="invoice-total"');

    expect($invoice->fresh()->total)->toBe('140.00');
});

test('pulling unbilled work invokes the AddLineItemsFromUnbilled action with the invoice id', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $this->mock(AddLineItemsFromUnbilled::class, function (MockInterface $mock) use ($invoice) {
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (AddLineItemsFromUnbilledInput $input): bool => $input->invoiceId === $invoice->id)
            ->andReturn(ActionResult::success($invoice));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('pullUnbilled')
        ->assertHasNoErrors();
});

test('sending invokes the SendInvoice action', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $this->mock(SendInvoice::class, function (MockInterface $mock) use ($invoice) {
        $mock->shouldReceive('execute')->once()->andReturn(ActionResult::success($invoice));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('send')
        ->assertHasNoErrors();
});

test('marking paid invokes the MarkInvoicePaid action', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $this->mock(MarkInvoicePaid::class, function (MockInterface $mock) use ($invoice) {
        $mock->shouldReceive('execute')->once()->andReturn(ActionResult::success($invoice));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('markPaid')
        ->assertHasNoErrors();
});

test('voiding invokes the VoidInvoice action', function () {
    $user = actAsUser();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $this->mock(VoidInvoice::class, function (MockInterface $mock) use ($invoice) {
        $mock->shouldReceive('execute')->once()->andReturn(ActionResult::success($invoice));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('void')
        ->assertHasNoErrors();
});

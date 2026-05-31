<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Invoice\DraftInvoice;
use App\Actions\Invoice\DraftInvoiceInput;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Livewire\Livewire;
use Mockery\MockInterface;

beforeEach(fn () => registerInvoiceRoutes());

test('drafting routes through the DraftInvoice action and redirects to the invoice', function () {
    $user = actAsUser();
    $client = Client::factory()->create();

    $invoice = Invoice::factory()->for($client)->create(['status' => InvoiceStatus::Draft]);

    $this->mock(DraftInvoice::class, function (MockInterface $mock) use ($client, $invoice) {
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (DraftInvoiceInput $input): bool => $input->clientId === $client->id && $input->notes === 'Q2 work')
            ->andReturn(ActionResult::success($invoice));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.create')
        ->set('clientId', $client->id)
        ->set('notes', 'Q2 work')
        ->call('draft')
        ->assertHasNoErrors()
        ->assertRedirect(route('invoices.show', $invoice));
});

test('drafting without a client fails validation and never calls the action', function () {
    $user = actAsUser();

    $this->mock(DraftInvoice::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('execute');
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.create')
        ->call('draft')
        ->assertHasErrors('clientId')
        ->assertNoRedirect();
});

test('an action failure surfaces an error and does not redirect', function () {
    $user = actAsUser();
    $client = Client::factory()->create();

    $this->mock(DraftInvoice::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn(ActionResult::failure(['authorization' => 'Not authorized to draft invoices.']));
    });

    Livewire::actingAs($user)
        ->test('pages::invoices.create')
        ->set('clientId', $client->id)
        ->call('draft')
        ->assertNoRedirect();
});

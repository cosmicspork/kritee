<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * The invoice pages link between each other by route name; until the routing
 * step wires them into the app, register them here so the views can resolve
 * route() during rendering.
 */
if (! function_exists('registerInvoiceRoutes')) {
    function registerInvoiceRoutes(): void
    {
        if (Route::has('invoices.index')) {
            return;
        }

        Route::livewire('invoices', 'pages::invoices.index')->name('invoices.index');
        Route::livewire('invoices/create', 'pages::invoices.create')->name('invoices.create');
        Route::livewire('invoices/{invoice}', 'pages::invoices.show')->name('invoices.show');
    }
}

beforeEach(fn () => registerInvoiceRoutes());

test('it lists invoices with their client and total', function () {
    $user = actAsUser();
    $client = Client::factory()->create(['name' => 'Acme Co']);
    $invoice = Invoice::factory()->for($client)->create([
        'invoice_number' => 'INV-2026-0001',
        'total' => '1234.00',
    ]);

    Livewire::actingAs($user)
        ->test('pages::invoices.index')
        ->assertSee('INV-2026-0001')
        ->assertSee('Acme Co')
        ->assertSee('1234.00')
        ->assertSeeHtml('data-test="invoice-link-'.$invoice->id.'"');
});

test('it filters invoices by status', function () {
    $user = actAsUser();
    Invoice::factory()->create(['invoice_number' => 'INV-2026-DRAFT', 'status' => InvoiceStatus::Draft]);
    Invoice::factory()->create(['invoice_number' => 'INV-2026-PAID', 'status' => InvoiceStatus::Paid]);

    Livewire::actingAs($user)
        ->test('pages::invoices.index')
        ->set('status', InvoiceStatus::Paid->value)
        ->assertSee('INV-2026-PAID')
        ->assertDontSee('INV-2026-DRAFT');
});

test('it shows an empty state when there are no invoices', function () {
    $user = actAsUser();

    Livewire::actingAs($user)
        ->test('pages::invoices.index')
        ->assertSeeHtml('data-test="invoices-empty"');
});

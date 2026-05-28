<?php

use App\Actions\Invoice\DraftInvoice;
use App\Actions\Invoice\DraftInvoiceInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function actingAsInvoiceUser(): User
{
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it opens a numbered draft for a client', function () {
    actingAsInvoiceUser();
    $client = Client::factory()->create();

    $result = app(DraftInvoice::class)->execute(
        DraftInvoiceInput::from(['client_id' => $client->getKey(), 'notes' => 'Q2 work']),
    );

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Invoice::class)
        ->and($result->data->status)->toBe(InvoiceStatus::Draft)
        ->and($result->data->client_id)->toBe($client->getKey())
        ->and($result->data->notes)->toBe('Q2 work')
        ->and($result->data->invoice_number)->toStartWith('INV-')
        ->and($result->data->subtotal)->toBe('0.00')
        ->and($result->data->total)->toBe('0.00');

    expect(Invoice::count())->toBe(1);
});

test('a non-user actor cannot draft an invoice', function () {
    app()->instance(Actor::class, new SystemActor);
    $client = Client::factory()->create();

    $result = app(DraftInvoice::class)->execute(
        DraftInvoiceInput::from(['client_id' => $client->getKey()]),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    expect(Invoice::count())->toBe(0);
});

test('a missing client fails validation', function () {
    actingAsInvoiceUser();

    expect(fn () => DraftInvoiceInput::validateAndCreate(['client_id' => 999999]))
        ->toThrow(ValidationException::class);
});

test('a repeated idempotency key drafts a single invoice', function () {
    actingAsInvoiceUser();
    $client = Client::factory()->create();

    $payload = ['client_id' => $client->getKey(), 'idempotency_key' => 'draft-once'];

    $first = app(DraftInvoice::class)->execute(DraftInvoiceInput::from($payload));
    $second = app(DraftInvoice::class)->execute(DraftInvoiceInput::from($payload));

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey());

    expect(Invoice::count())->toBe(1);
});

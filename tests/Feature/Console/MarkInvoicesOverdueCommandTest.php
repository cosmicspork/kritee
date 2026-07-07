<?php

use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

test('the command sweeps overdue invoices as the system actor by default', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'due_at' => now()->subDays(5)]);

    $this->artisan('invoices:mark-overdue')
        ->expectsOutputToContain('Marked 1 invoice(s) overdue.')
        ->assertExitCode(0);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and(app(Actor::class))->toBeInstanceOf(SystemActor::class);
});

test('--user runs the sweep as that user', function () {
    $user = User::factory()->create();
    Invoice::factory()->create(['status' => InvoiceStatus::Viewed, 'due_at' => now()->subDay()]);

    $this->artisan('invoices:mark-overdue', ['--user' => $user->id])
        ->assertExitCode(0);

    $actor = app(Actor::class);

    expect($actor)->toBeInstanceOf(UserActor::class)
        ->and($actor->id())->toBe((string) $user->id);
});

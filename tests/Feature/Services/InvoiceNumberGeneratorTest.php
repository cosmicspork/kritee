<?php

use App\Models\Invoice;
use App\Services\Billing\InvoiceNumberGenerator;

test('the first number of a year starts at a padded one', function () {
    expect(app(InvoiceNumberGenerator::class)->next(2026))->toBe('INV-2026-0001');
});

test('it advances past the highest number issued in the same year', function () {
    Invoice::factory()->create(['invoice_number' => 'INV-2026-0007']);
    Invoice::factory()->create(['invoice_number' => 'INV-2026-0023']);

    expect(app(InvoiceNumberGenerator::class)->next(2026))->toBe('INV-2026-0024');
});

test('the sequence is scoped per year', function () {
    Invoice::factory()->create(['invoice_number' => 'INV-2025-0099']);

    expect(app(InvoiceNumberGenerator::class)->next(2026))->toBe('INV-2026-0001');
});

test('it produces a unique number when applied repeatedly within a year', function () {
    $generator = app(InvoiceNumberGenerator::class);

    $first = $generator->next(2026);
    Invoice::factory()->create(['invoice_number' => $first]);

    $second = $generator->next(2026);

    expect($second)->not->toBe($first)
        ->and(Invoice::query()->where('invoice_number', $second)->exists())->toBeFalse();
});

<?php

use App\Services\Billing\InvoiceTotals;

test('it sums line items into a subtotal with no tax', function () {
    $totals = app(InvoiceTotals::class)->compute([
        ['quantity' => 2, 'unit_price' => 100.00],
        ['quantity' => 3, 'unit_price' => 50.00],
    ]);

    expect($totals)->toBe([
        'subtotal' => '350.00',
        'tax_amount' => '0.00',
        'total' => '350.00',
    ]);
});

test('it applies a tax rate to the subtotal', function () {
    $totals = app(InvoiceTotals::class)->compute([
        ['quantity' => 1, 'unit_price' => 1000.00],
    ], 0.20);

    expect($totals)->toBe([
        'subtotal' => '1000.00',
        'tax_amount' => '200.00',
        'total' => '1200.00',
    ]);
});

test('it rounds fractional quantities and prices to cents', function () {
    $totals = app(InvoiceTotals::class)->compute([
        ['quantity' => 1.5, 'unit_price' => 33.33],
        ['quantity' => 0.25, 'unit_price' => 80.00],
    ], 0.0825);

    expect($totals['subtotal'])->toBe('70.00')
        ->and($totals['tax_amount'])->toBe('5.78')
        ->and($totals['total'])->toBe('75.78');
});

test('an empty set of line items totals to zero', function () {
    $totals = app(InvoiceTotals::class)->compute([], 0.20);

    expect($totals)->toBe([
        'subtotal' => '0.00',
        'tax_amount' => '0.00',
        'total' => '0.00',
    ]);
});

test('a null tax rate is treated as no tax', function () {
    $totals = app(InvoiceTotals::class)->compute([
        ['quantity' => 4, 'unit_price' => 25.00],
    ], null);

    expect($totals)->toBe([
        'subtotal' => '100.00',
        'tax_amount' => '0.00',
        'total' => '100.00',
    ]);
});

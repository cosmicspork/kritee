<?php

namespace App\Services\Billing;

use Illuminate\Support\Collection;

/**
 * Computes the subtotal, tax, and grand total for a set of invoice line
 * items. Pure arithmetic over `quantity` and `unit_price`; the optional tax
 * rate is a fraction (0.20 == 20%). Money is rounded to two places and tax to
 * the same precision the `invoices` table stores.
 */
final class InvoiceTotals
{
    /**
     * @param  iterable<array{quantity: int|float|string, unit_price: int|float|string}>  $lineItems
     * @return array{subtotal: string, tax_amount: string, total: string}
     */
    public function compute(iterable $lineItems, int|float|string|null $taxRate = null): array
    {
        $subtotal = Collection::make($lineItems)->reduce(
            fn (float $carry, array $item): float => $carry
                + (float) $item['quantity'] * (float) $item['unit_price'],
            0.0,
        );

        $subtotal = round($subtotal, 2);
        $taxAmount = round($subtotal * (float) ($taxRate ?? 0), 2);
        $total = round($subtotal + $taxAmount, 2);

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_amount' => number_format($taxAmount, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ];
    }
}

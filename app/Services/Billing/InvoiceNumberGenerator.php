<?php

namespace App\Services\Billing;

use App\Models\Invoice;

/**
 * Produces the next invoice number for a year in the
 * `INV-<year>-<zero-padded-seq>` form. The sequence is scoped per year and
 * derived from the highest suffix already issued, so each year restarts at one.
 */
final class InvoiceNumberGenerator
{
    private const PREFIX = 'INV-';

    private const PAD_LENGTH = 4;

    public function next(int $year): string
    {
        $prefix = self::PREFIX.$year.'-';

        $highest = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->get(['invoice_number'])
            ->map(fn (Invoice $invoice): int => $this->sequenceOf($invoice->invoice_number, $prefix))
            ->max() ?? 0;

        $sequence = str_pad((string) ($highest + 1), self::PAD_LENGTH, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }

    private function sequenceOf(string $invoiceNumber, string $prefix): int
    {
        return (int) substr($invoiceNumber, strlen($prefix));
    }
}

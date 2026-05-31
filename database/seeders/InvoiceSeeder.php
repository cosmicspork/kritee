<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        $statuses = [
            InvoiceStatus::Draft,
            InvoiceStatus::Sent,
            InvoiceStatus::Sent,
            InvoiceStatus::Paid,
            InvoiceStatus::Paid,
            InvoiceStatus::Overdue,
            InvoiceStatus::Void,
            InvoiceStatus::Draft,
        ];

        foreach ($statuses as $status) {
            $invoice = Invoice::factory()->for($clients->random())->create([
                'status' => $status,
            ]);

            LineItem::factory()->count(random_int(2, 4))->for($invoice)->create();

            $this->applyTotalsAndTimeline($invoice, $status);
        }
    }

    private function applyTotalsAndTimeline(Invoice $invoice, InvoiceStatus $status): void
    {
        $subtotal = (float) $invoice->lineItems()->sum('amount');
        $taxRate = 0.0875;
        $taxAmount = round($subtotal * $taxRate, 2);

        $issuedAt = $status === InvoiceStatus::Draft
            ? null
            : Carbon::today()->subDays(random_int(10, 60));

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
            'issued_at' => $issuedAt,
            'due_at' => $issuedAt?->copy()->addDays(30),
            'paid_at' => $status === InvoiceStatus::Paid
                ? $issuedAt?->copy()->addDays(random_int(1, 25))
                : null,
        ]);
    }
}

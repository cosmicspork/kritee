<?php

namespace App\Actions\Invoice;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\TimeEntry;
use App\Services\Billing\BillingRateCascade;
use App\Services\Billing\InvoiceTotals;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Draws a client's outstanding billable work onto a draft invoice: each
 * unbilled time entry and expense becomes a line item, the source is stamped
 * billed inside the same transaction, and the invoice totals are recomputed.
 *
 * Time entries snapshot the rate resolved by the billing cascade onto
 * `billed_rate` so a later rate change cannot retroactively alter an issued
 * invoice; their amount is the snapshot rate times the logged hours.
 */
class AddLineItemsFromUnbilled implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function __construct(
        private readonly BillingRateCascade $rates,
        private readonly InvoiceTotals $totals,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof AddLineItemsFromUnbilledInput) {
            return ActionResult::failure(['input' => 'Expected '.AddLineItemsFromUnbilledInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to bill an invoice.']);
        }

        $invoice = Invoice::find($input->invoiceId);

        if ($invoice === null) {
            return ActionResult::failure(['invoice' => 'Invoice not found.']);
        }

        if ($actor->user()->cannot('addLineItems', $invoice)) {
            return ActionResult::failure(['status' => 'Line items can only be added to a draft invoice.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($invoice): ActionResult {
            $invoice = DB::transaction(function () use ($invoice): Invoice {
                $clientId = (int) $invoice->client_id;
                $sortOrder = (int) $invoice->lineItems()->max('sort_order');

                foreach ($this->unbilledTimeEntries($clientId) as $entry) {
                    $rate = $this->rates->resolve($entry, $this->entryDate($entry));

                    if ($rate === null) {
                        continue;
                    }

                    $hours = round((int) $entry->duration_minutes / 60, 2);

                    $entry->forceFill([
                        'is_billed' => true,
                        'billed_rate' => $rate,
                    ])->save();

                    $invoice->lineItems()->create([
                        'description' => $this->timeEntryDescription($entry),
                        'quantity' => number_format($hours, 2, '.', ''),
                        'unit_price' => $rate,
                        'amount' => number_format(round($hours * (float) $rate, 2), 2, '.', ''),
                        'sort_order' => ++$sortOrder,
                    ]);
                }

                foreach ($this->unbilledExpenses($clientId) as $expense) {
                    $expense->forceFill(['is_billed' => true])->save();

                    $invoice->lineItems()->create([
                        'description' => $expense->description,
                        'quantity' => '1.00',
                        'unit_price' => $expense->amount,
                        'amount' => $expense->amount,
                        'sort_order' => ++$sortOrder,
                    ]);
                }

                $this->applyTotals($invoice);

                return $invoice->fresh() ?? $invoice;
            });

            return ActionResult::success($invoice);
        });
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    private function unbilledTimeEntries(int $clientId): Collection
    {
        return TimeEntry::query()
            ->where('client_id', $clientId)
            ->where('is_billable', true)
            ->where('is_billed', false)
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Expense>
     */
    private function unbilledExpenses(int $clientId): Collection
    {
        return Expense::query()
            ->where('client_id', $clientId)
            ->where('is_billable', true)
            ->where('is_billed', false)
            ->orderBy('incurred_on')
            ->orderBy('id')
            ->get();
    }

    private function entryDate(TimeEntry $entry): CarbonImmutable
    {
        $startedAt = $entry->started_at;

        return $startedAt === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($startedAt);
    }

    private function timeEntryDescription(TimeEntry $entry): string
    {
        return $entry->description ?? 'Time entry #'.$entry->getKey();
    }

    private function applyTotals(Invoice $invoice): void
    {
        $lineItems = $invoice->lineItems()
            ->get(['quantity', 'unit_price'])
            ->map(fn (LineItem $item): array => [
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
            ]);

        $taxRate = $invoice->tax_rate;

        $totals = $this->totals->compute(
            $lineItems,
            is_scalar($taxRate) ? $taxRate : null,
        );

        $invoice->forceFill([
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
        ])->save();
    }
}

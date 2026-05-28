<?php

namespace App\Actions\Invoice;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Settles an issued invoice: records the payment timestamp and moves it to paid.
 */
class MarkInvoicePaid implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof MarkInvoicePaidInput) {
            return ActionResult::failure(['input' => 'Expected '.MarkInvoicePaidInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to mark an invoice paid.']);
        }

        $invoice = Invoice::find($input->invoiceId);

        if ($invoice === null) {
            return ActionResult::failure(['invoice' => 'Invoice not found.']);
        }

        if ($actor->user()->cannot('markPaid', $invoice)) {
            return ActionResult::failure(['status' => 'Only an issued invoice can be marked paid.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($invoice, $input): ActionResult {
            $invoice = DB::transaction(function () use ($invoice, $input): Invoice {
                $invoice->forceFill([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => $input->paidAt ?? CarbonImmutable::now(),
                ])->save();

                return $invoice;
            });

            InvoicePaid::dispatch($invoice);

            return ActionResult::success($invoice);
        });
    }
}

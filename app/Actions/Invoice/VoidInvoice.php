<?php

namespace App\Actions\Invoice;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Voids an invoice. A paid invoice is settled and cannot be voided.
 */
class VoidInvoice implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof VoidInvoiceInput) {
            return ActionResult::failure(['input' => 'Expected '.VoidInvoiceInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to void an invoice.']);
        }

        $invoice = Invoice::find($input->invoiceId);

        if ($invoice === null) {
            return ActionResult::failure(['invoice' => 'Invoice not found.']);
        }

        if ($actor->user()->cannot('void', $invoice)) {
            return ActionResult::failure(['status' => 'A paid invoice cannot be voided.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($invoice): ActionResult {
            $invoice = DB::transaction(function () use ($invoice): Invoice {
                $invoice->forceFill(['status' => InvoiceStatus::Void])->save();

                return $invoice;
            });

            return ActionResult::success($invoice);
        });
    }
}

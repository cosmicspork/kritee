<?php

namespace App\Actions\Invoice;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceSent;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Issues a draft invoice: stamps the issue date and moves it to sent.
 */
class SendInvoice implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof SendInvoiceInput) {
            return ActionResult::failure(['input' => 'Expected '.SendInvoiceInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to send an invoice.']);
        }

        $invoice = Invoice::find($input->invoiceId);

        if ($invoice === null) {
            return ActionResult::failure(['invoice' => 'Invoice not found.']);
        }

        if ($actor->user()->cannot('send', $invoice)) {
            return ActionResult::failure(['status' => 'Only a draft invoice can be sent.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($invoice, $input): ActionResult {
            $invoice = DB::transaction(function () use ($invoice, $input): Invoice {
                $invoice->forceFill([
                    'status' => InvoiceStatus::Sent,
                    'issued_at' => CarbonImmutable::now()->toDateString(),
                ]);

                if ($input->dueAt !== null) {
                    $invoice->forceFill(['due_at' => $input->dueAt]);
                }

                $invoice->save();

                return $invoice;
            });

            InvoiceSent::dispatch($invoice);

            return ActionResult::success($invoice);
        });
    }
}

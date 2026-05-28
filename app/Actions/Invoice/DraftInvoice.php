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
use App\Services\Billing\InvoiceNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Opens a fresh draft invoice for a client, assigning the next number in the
 * client-agnostic yearly sequence.
 */
class DraftInvoice implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function __construct(private readonly InvoiceNumberGenerator $numbers) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof DraftInvoiceInput) {
            return ActionResult::failure(['input' => 'Expected '.DraftInvoiceInput::class.'.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user actor is required to draft an invoice.']);
        }

        if ($actor->user()->cannot('create', Invoice::class)) {
            return ActionResult::failure(['authorization' => 'Not authorized to draft invoices.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            $invoice = DB::transaction(function () use ($input): Invoice {
                return Invoice::create([
                    'invoice_number' => $this->numbers->next(CarbonImmutable::now()->year),
                    'client_id' => $input->clientId,
                    'status' => InvoiceStatus::Draft,
                    'subtotal' => '0.00',
                    'total' => '0.00',
                    'notes' => $input->notes,
                    'terms' => $input->terms,
                ]);
            });

            return ActionResult::success($invoice);
        });
    }
}

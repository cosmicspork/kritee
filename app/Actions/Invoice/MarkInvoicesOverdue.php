<?php

namespace App\Actions\Invoice;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceMarkedOverdue;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Sweeps issued invoices past their due date into the overdue status. Runs
 * on the scheduler as the SystemActor; a user actor may trigger it too.
 */
class MarkInvoicesOverdue implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        assert($input instanceof MarkInvoicesOverdueInput);

        if ($this->actor instanceof UserActor && $this->actor->user()->cannot('create', Invoice::class)) {
            return ActionResult::failure(['actor' => 'Not allowed to update invoices.']);
        }

        return $this->idempotently($input->idempotencyKey, function (): ActionResult {
            $invoices = DB::transaction(function () {
                $due = Invoice::query()
                    ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Viewed])
                    ->whereNotNull('due_at')
                    ->whereDate('due_at', '<', CarbonImmutable::now()->toDateString())
                    ->lockForUpdate()
                    ->get();

                foreach ($due as $invoice) {
                    $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();
                }

                return $due;
            });

            foreach ($invoices as $invoice) {
                InvoiceMarkedOverdue::dispatch($invoice);
            }

            return ActionResult::success($invoices);
        });
    }
}

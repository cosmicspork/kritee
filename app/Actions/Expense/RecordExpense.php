<?php

namespace App\Actions\Expense;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\ExpenseRecorded;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class RecordExpense implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  RecordExpenseInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'An expense must be recorded by a user.']);
        }

        if ($actor->user()->cannot('create', Expense::class)) {
            return ActionResult::failure(['authorization' => 'You may not record expenses.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $actor): ActionResult {
            $expense = DB::transaction(function () use ($input, $actor): Expense {
                $expense = Expense::create([
                    'user_id' => $input->userId,
                    'client_id' => $input->clientId,
                    'project_id' => $input->projectId,
                    'ticket_id' => $input->ticketId,
                    'description' => $input->description,
                    'amount' => $input->amount,
                    'incurred_on' => $input->incurredOn,
                    'category' => $input->category,
                    'is_billable' => $input->isBillable,
                    'notes' => $input->notes,
                ]);

                $this->attachReceipt($expense, $input->receipt, $actor->user());

                return $expense;
            });

            ExpenseRecorded::dispatch($expense, $actor->id());

            return ActionResult::success($expense->fresh());
        });
    }

    private function attachReceipt(Expense $expense, ReceiptData|Optional|null $receipt, User $uploader): void
    {
        if (! $receipt instanceof ReceiptData) {
            return;
        }

        $expense->attachments()->create([
            'uploaded_by' => $uploader->getKey(),
            'filename' => $receipt->filename,
            'path' => $receipt->path,
            'mime_type' => $receipt->mimeType,
            'size_bytes' => $receipt->sizeBytes,
        ]);
    }
}

<?php

namespace App\Actions\Expense;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdateExpense implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  UpdateExpenseInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'An expense must be updated by a user.']);
        }

        $expense = Expense::find($input->expenseId);

        if ($expense === null) {
            return ActionResult::failure(['expense_id' => 'Expense not found.']);
        }

        if ($actor->user()->cannot('update', $expense)) {
            return ActionResult::failure(['authorization' => 'You may not update this expense.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $expense): ActionResult {
            $changes = $this->changes($input);

            DB::transaction(function () use ($expense, $changes): void {
                if ($changes !== []) {
                    $expense->update($changes);
                }
            });

            return ActionResult::success($expense->fresh());
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(UpdateExpenseInput $input): array
    {
        $candidates = [
            'amount' => $input->amount,
            'incurred_on' => $input->incurredOn,
            'description' => $input->description,
            'client_id' => $input->clientId,
            'project_id' => $input->projectId,
            'ticket_id' => $input->ticketId,
            'category' => $input->category,
            'is_billable' => $input->isBillable,
            'notes' => $input->notes,
        ];

        return array_filter(
            $candidates,
            fn (mixed $value): bool => ! $value instanceof Optional,
        );
    }
}

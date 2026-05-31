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

class DeleteExpense implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  DeleteExpenseInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'An expense must be deleted by a user.']);
        }

        $expense = Expense::find($input->expenseId);

        if ($expense === null) {
            return ActionResult::failure(['expense_id' => 'Expense not found.']);
        }

        if ($actor->user()->cannot('delete', $expense)) {
            return ActionResult::failure(['authorization' => 'You may not delete this expense.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($expense): ActionResult {
            DB::transaction(function () use ($expense): void {
                $expense->attachments()->delete();
                $expense->delete();
            });

            return ActionResult::success(['expense_id' => $expense->getKey()]);
        });
    }
}

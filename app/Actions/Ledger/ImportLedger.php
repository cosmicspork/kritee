<?php

namespace App\Actions\Ledger;

use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actions\Expense\RecordExpense;
use App\Actions\Expense\RecordExpenseInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\LedgerRowStatus;
use App\Models\Expense;
use App\Services\Ledger\LedgerImportPlanner;
use Throwable;

/**
 * Imports a decoded JSONL expense ledger. The planner classifies every row first;
 * each importable row is then recorded through {@see RecordExpense}, so the unit
 * of work, its event and its idempotency live on that action. Rows are recorded
 * independently (no outer transaction): a bad row is reported, not fatal — which
 * also avoids rolling back already-dispatched ExpenseRecorded events.
 */
class ImportLedger implements Action
{
    public function __construct(
        private readonly Actor $actor,
        private readonly LedgerImportPlanner $planner,
        private readonly RecordExpense $recordExpense,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        assert($input instanceof ImportLedgerInput);

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'Ledger import must run as a user.']);
        }

        if ($this->actor->user()->cannot('create', Expense::class)) {
            return ActionResult::failure(['authorization' => 'You may not record expenses.']);
        }

        $planned = $this->planner->plan($input->rows, $input->userId);

        $imported = 0;
        $writeErrors = [];

        if (! $input->dryRun) {
            foreach ($planned as $row) {
                if ($row->status !== LedgerRowStatus::Import) {
                    continue;
                }

                try {
                    $result = $this->recordExpense->execute(RecordExpenseInput::from($row->attributes ?? []));

                    if ($result->success) {
                        $imported++;
                    } else {
                        $writeErrors[] = ['line' => $row->line, 'message' => $this->flatten($result->errors)];
                    }
                } catch (Throwable $e) {
                    $writeErrors[] = ['line' => $row->line, 'message' => $e->getMessage()];
                }
            }
        }

        return ActionResult::success([
            'dry_run' => $input->dryRun,
            'imported' => $imported,
            'planned' => $planned,
            'write_errors' => $writeErrors,
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function flatten(array $errors): string
    {
        $parts = [];
        foreach ($errors as $value) {
            $parts[] = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
        }

        return implode('; ', $parts);
    }
}

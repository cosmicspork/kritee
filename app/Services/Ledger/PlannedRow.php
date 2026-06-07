<?php

namespace App\Services\Ledger;

use App\Enums\LedgerRowStatus;

/**
 * The planner's verdict for one source line: its status, the computed `ref`, the
 * resolved attributes ready for the RecordExpense action when it is an import,
 * and a human message for skips/errors.
 */
final class PlannedRow
{
    /**
     * @param  array<string, mixed>|null  $attributes
     */
    public function __construct(
        public readonly int $line,
        public readonly LedgerRowStatus $status,
        public readonly ?string $ref = null,
        public readonly ?array $attributes = null,
        public readonly ?string $message = null,
        public readonly bool $drift = false,
    ) {}
}

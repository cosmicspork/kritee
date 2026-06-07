<?php

namespace App\Actions\Ledger;

use App\Actions\Contracts\ActionInput;

final class ImportLedgerInput extends ActionInput
{
    /**
     * @param  array<int, array<string, mixed>>  $rows  decoded JSONL rows, each tagged with `_line`
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $rows = [],
        public readonly bool $dryRun = false,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

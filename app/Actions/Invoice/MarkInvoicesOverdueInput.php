<?php

namespace App\Actions\Invoice;

use App\Actions\Contracts\ActionInput;

final class MarkInvoicesOverdueInput extends ActionInput
{
    public function __construct(
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

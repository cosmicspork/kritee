<?php

namespace App\Actions\Invoice;

use App\Actions\Contracts\ActionInput;
use App\Models\Invoice;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;

final class SendInvoiceInput extends ActionInput
{
    public function __construct(
        #[Required, Exists(Invoice::class, 'id')]
        public readonly int $invoiceId,
        #[Nullable]
        public readonly ?string $dueAt = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

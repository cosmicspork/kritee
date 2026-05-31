<?php

namespace App\Actions\Invoice;

use App\Actions\Contracts\ActionInput;
use App\Models\Client;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;

final class DraftInvoiceInput extends ActionInput
{
    public function __construct(
        #[Required, Exists(Client::class, 'id')]
        public readonly int $clientId,
        #[Nullable]
        public readonly ?string $notes = null,
        #[Nullable]
        public readonly ?string $terms = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

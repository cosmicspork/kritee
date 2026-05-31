<?php

namespace App\Actions\Client;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;

final class ArchiveClientInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('clients', 'id')]
        public readonly int $clientId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

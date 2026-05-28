<?php

namespace App\Actions\Client;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

final class UpdateClientInput extends ActionInput
{
    public function __construct(
        #[Required, Exists('clients', 'id')]
        public readonly int $clientId,
        #[Required, StringType, Max(255)]
        public readonly string $name,
        #[Nullable, Email, Max(255)]
        public readonly ?string $email = null,
        #[Nullable, StringType, Max(255)]
        public readonly ?string $phone = null,
        #[Nullable, StringType]
        public readonly ?string $address = null,
        #[Nullable, StringType]
        public readonly ?string $notes = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

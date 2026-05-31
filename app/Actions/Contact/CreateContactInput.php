<?php

namespace App\Actions\Contact;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;

final class CreateContactInput extends ActionInput
{
    public function __construct(
        #[Exists('clients', 'id')]
        public readonly int $clientId,
        #[Max(255)]
        public readonly string $name,
        #[Email, Max(255)]
        public readonly ?string $email = null,
        #[Max(50)]
        public readonly ?string $phone = null,
        #[Max(255)]
        public readonly ?string $title = null,
        public readonly bool $isPrimary = false,
        public readonly ?string $notes = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

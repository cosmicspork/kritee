<?php

namespace App\Actions\Contact;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;

final class DeleteContactInput extends ActionInput
{
    public function __construct(
        #[Exists('contacts', 'id')]
        public readonly int $contactId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

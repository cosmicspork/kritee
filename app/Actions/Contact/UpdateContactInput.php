<?php

namespace App\Actions\Contact;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Optional;

final class UpdateContactInput extends ActionInput
{
    /**
     * Fields default to {@see Optional} so a caller may patch a subset without
     * clearing the values it omits.
     */
    public function __construct(
        #[Exists('contacts', 'id')]
        public readonly int $contactId,
        #[Max(255)]
        public readonly string|Optional $name = new Optional,
        #[Email, Max(255)]
        public readonly string|null|Optional $email = new Optional,
        #[Max(50)]
        public readonly string|null|Optional $phone = new Optional,
        #[Max(255)]
        public readonly string|null|Optional $title = new Optional,
        public readonly bool|Optional $isPrimary = new Optional,
        public readonly string|null|Optional $notes = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

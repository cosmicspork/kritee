<?php

namespace App\Actions\Document;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Partial update: only fields present on the input are written. `title` keeps its
 * required shape when supplied but may be omitted to leave the stored value
 * untouched.
 */
#[MapName(SnakeCaseMapper::class)]
final class UpdateDocumentInput extends ActionInput
{
    public function __construct(
        public readonly int $documentId,
        #[Max(255)]
        public readonly string|Optional $title = new Optional,
        public readonly string|null|Optional $content = new Optional,
        public readonly int|null|Optional $clientId = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

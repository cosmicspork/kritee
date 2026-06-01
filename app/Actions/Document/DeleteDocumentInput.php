<?php

namespace App\Actions\Document;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class DeleteDocumentInput extends ActionInput
{
    public function __construct(
        public readonly int $documentId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

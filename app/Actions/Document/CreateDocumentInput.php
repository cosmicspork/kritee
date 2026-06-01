<?php

namespace App\Actions\Document;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * @property string|Optional|null $idempotencyKey
 */
#[MapName(SnakeCaseMapper::class)]
final class CreateDocumentInput extends ActionInput
{
    /**
     * @param  DocumentFileData|Optional|null  $file  Optional file metadata persisted as a morphed Attachment.
     */
    public function __construct(
        public readonly int $uploadedBy,
        #[Required, Max(255)]
        public readonly string $title,
        public readonly ?string $content = null,
        public readonly ?int $clientId = null,
        public readonly DocumentFileData|Optional|null $file = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

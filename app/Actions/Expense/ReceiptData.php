<?php

namespace App\Actions\Expense;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Receipt file metadata accepted by an expense action. Storage is the caller's
 * concern: the action persists the already-resolved `path` and descriptors as
 * an Attachment, never moving bytes itself.
 */
#[MapName(SnakeCaseMapper::class)]
final class ReceiptData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $filename,
        #[Required]
        public readonly string $path,
        #[Required]
        public readonly string $mimeType,
        #[Required, Min(0)]
        public readonly int $sizeBytes,
    ) {}
}

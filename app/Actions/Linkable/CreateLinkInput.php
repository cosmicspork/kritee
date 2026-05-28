<?php

namespace App\Actions\Linkable;

use App\Actions\Contracts\ActionInput;
use App\Enums\LinkRelationshipType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class CreateLinkInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly string $sourceType,
        #[Required]
        public readonly int|string $sourceId,
        #[Required]
        public readonly string $targetType,
        #[Required]
        public readonly int|string $targetId,
        #[Required]
        public readonly LinkRelationshipType $relationshipType,
        #[Max(2000)]
        public readonly ?string $note = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

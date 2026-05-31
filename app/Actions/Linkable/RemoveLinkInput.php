<?php

namespace App\Actions\Linkable;

use App\Actions\Contracts\ActionInput;
use App\Enums\LinkRelationshipType;
use Spatie\LaravelData\Attributes\Validation\Required;

final class RemoveLinkInput extends ActionInput
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
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

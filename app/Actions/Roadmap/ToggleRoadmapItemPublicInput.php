<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Required;

final class ToggleRoadmapItemPublicInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapItemId,
        #[Required]
        public readonly bool $isPublic,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

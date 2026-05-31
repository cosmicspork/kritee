<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;

final class MoveRoadmapItemInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapItemId,
        #[Required, Min(0)]
        public readonly int $sortOrder,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

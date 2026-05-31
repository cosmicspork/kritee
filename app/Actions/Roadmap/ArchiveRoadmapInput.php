<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Required;

final class ArchiveRoadmapInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

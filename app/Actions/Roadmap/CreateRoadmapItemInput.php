<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use App\Enums\RoadmapItemStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class CreateRoadmapItemInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapId,
        #[Required, Max(255)]
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly RoadmapItemStatus $status = RoadmapItemStatus::Planned,
        public readonly ?string $startsAt = null,
        public readonly ?string $endsAt = null,
        public readonly bool $isPublic = false,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use App\Enums\RoadmapItemStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Optional;

final class UpdateRoadmapItemInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapItemId,
        #[Max(255)]
        public readonly string|Optional $title = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly RoadmapItemStatus|Optional $status = new Optional,
        public readonly string|null|Optional $startsAt = new Optional,
        public readonly string|null|Optional $endsAt = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

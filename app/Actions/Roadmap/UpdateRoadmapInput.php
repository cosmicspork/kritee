<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Optional;

final class UpdateRoadmapInput extends ActionInput
{
    public function __construct(
        #[Required]
        public readonly int $roadmapId,
        #[Max(255)]
        public readonly string|Optional $title = new Optional,
        public readonly string|null|Optional $description = new Optional,
        public readonly int|null|Optional $clientId = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

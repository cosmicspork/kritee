<?php

namespace App\Actions\Roadmap;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class CreateRoadmapInput extends ActionInput
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?int $clientId = null,
        public readonly bool $isPublic = false,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

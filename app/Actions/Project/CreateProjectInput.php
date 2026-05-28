<?php

namespace App\Actions\Project;

use App\Actions\Contracts\ActionInput;
use App\Enums\ProjectStatus;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;

final class CreateProjectInput extends ActionInput
{
    public function __construct(
        #[StringType, Max(255)]
        public readonly string $name,
        #[Exists('clients', 'id')]
        public readonly ?int $clientId = null,
        #[StringType]
        public readonly ?string $description = null,
        #[Enum(ProjectStatus::class)]
        public readonly ?ProjectStatus $status = null,
        #[Numeric, Min(0)]
        public readonly ?string $budget = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $endsAt = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

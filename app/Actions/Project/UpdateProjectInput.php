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
use Spatie\LaravelData\Optional;

/**
 * Only the properties supplied by the caller are applied; anything left as
 * {@see Optional} keeps its persisted value, so a partial update never clears
 * untouched columns.
 */
final class UpdateProjectInput extends ActionInput
{
    public function __construct(
        #[Exists('projects', 'id')]
        public readonly int $projectId,
        #[StringType, Max(255)]
        public readonly string|Optional $name = new Optional,
        #[Exists('clients', 'id')]
        public readonly int|null|Optional $clientId = new Optional,
        #[StringType]
        public readonly string|null|Optional $description = new Optional,
        #[Enum(ProjectStatus::class)]
        public readonly ProjectStatus|Optional $status = new Optional,
        #[Numeric, Min(0)]
        public readonly string|null|Optional $budget = new Optional,
        public readonly string|null|Optional $startsAt = new Optional,
        public readonly string|null|Optional $endsAt = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

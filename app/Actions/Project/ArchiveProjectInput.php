<?php

namespace App\Actions\Project;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;

final class ArchiveProjectInput extends ActionInput
{
    public function __construct(
        #[Exists('projects', 'id')]
        public readonly int $projectId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

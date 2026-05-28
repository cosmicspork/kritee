<?php

namespace App\Actions\Comment;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;

final class AddCommentInput extends ActionInput
{
    public function __construct(
        #[Exists('tickets', 'id')]
        public readonly int $ticketId,
        #[StringType, Max(10000)]
        public readonly string $content,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

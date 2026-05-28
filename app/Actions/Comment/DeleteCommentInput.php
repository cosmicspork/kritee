<?php

namespace App\Actions\Comment;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\Validation\Exists;

final class DeleteCommentInput extends ActionInput
{
    public function __construct(
        #[Exists('comments', 'id')]
        public readonly int $commentId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

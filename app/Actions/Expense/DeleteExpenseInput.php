<?php

namespace App\Actions\Expense;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class DeleteExpenseInput extends ActionInput
{
    public function __construct(
        public readonly int $expenseId,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

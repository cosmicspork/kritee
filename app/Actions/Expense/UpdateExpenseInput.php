<?php

namespace App\Actions\Expense;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * Partial update: only fields present on the input are written. `amount` and
 * `incurred_on` keep their required shape when supplied but may be omitted to
 * leave the stored value untouched.
 */
#[MapName(SnakeCaseMapper::class)]
final class UpdateExpenseInput extends ActionInput
{
    public function __construct(
        public readonly int $expenseId,
        #[Numeric, Min(0)]
        public readonly string|Optional $amount = new Optional,
        #[Date]
        public readonly string|Optional $incurredOn = new Optional,
        public readonly string|Optional $description = new Optional,
        public readonly int|null|Optional $clientId = new Optional,
        public readonly int|null|Optional $projectId = new Optional,
        public readonly int|null|Optional $ticketId = new Optional,
        public readonly string|null|Optional $category = new Optional,
        public readonly bool|Optional $isBillable = new Optional,
        public readonly string|null|Optional $notes = new Optional,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

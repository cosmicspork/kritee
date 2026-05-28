<?php

namespace App\Actions\Expense;

use App\Actions\Contracts\ActionInput;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * @property string|Optional|null $idempotencyKey
 */
#[MapName(SnakeCaseMapper::class)]
final class RecordExpenseInput extends ActionInput
{
    /**
     * @param  ReceiptData|Optional|null  $receipt  Optional receipt metadata persisted as a morphed Attachment.
     */
    public function __construct(
        public readonly int $userId,
        #[Required, Numeric, Min(0)]
        public readonly string $amount,
        #[Required, Date]
        public readonly string $incurredOn,
        public readonly string $description = '',
        public readonly ?int $clientId = null,
        public readonly ?int $projectId = null,
        public readonly ?int $ticketId = null,
        public readonly ?string $category = null,
        public readonly bool $isBillable = true,
        public readonly ?string $notes = null,
        public readonly ReceiptData|Optional|null $receipt = null,
        ?string $idempotencyKey = null,
    ) {
        parent::__construct($idempotencyKey);
    }
}

<?php

namespace App\Actions\Contracts;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Base input DTO for every action.
 */
#[MapName(SnakeCaseMapper::class)]
abstract class ActionInput extends Data
{
    public function __construct(
        public readonly ?string $idempotencyKey = null,
    ) {}
}

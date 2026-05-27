<?php

namespace App\Actions\Contracts;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Base input DTO for every action.
 *
 * Carries the idempotency contract shared by all actions: a retrying caller
 * (notably the future agent layer) re-sends the same key, letting the action
 * short-circuit instead of repeating a side effect. Concrete inputs extend
 * this, add their own readonly promoted properties, and forward the key to
 * `parent::__construct`.
 *
 * Snake-case name mapping keeps the serialized shape (`idempotency_key`,
 * `client_id`, …) consistent across the whole DTO family — the same shape the
 * agent layer will derive tool JSON schemas from.
 */
#[MapName(SnakeCaseMapper::class)]
abstract class ActionInput extends Data
{
    public function __construct(
        public readonly ?string $idempotencyKey = null,
    ) {}
}

<?php

namespace App\Actions\Contracts;

/**
 * Uniform result returned by every action.
 *
 * One shape for all callers (HTTP, CLI, jobs, future agent tools): a success
 * flag, an optional payload, and a list of errors. Build it through the named
 * constructors so the success/failure invariant cannot be set inconsistently.
 */
final class ActionResult
{
    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly array $errors = [],
    ) {}

    public static function success(mixed $data = null): self
    {
        return new self(success: true, data: $data);
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    public static function failure(array $errors): self
    {
        return new self(success: false, errors: $errors);
    }
}

<?php

namespace App\Services\Support;

use Normalizer;

/**
 * Computes the content-hash `ref` that dedups ledger rows across re-imports and
 * web entry.
 *
 * Each part is trimmed and Unicode NFC-normalised, the parts are joined with a
 * single LF, and the SHA-256 lowercase hex of the UTF-8 bytes is the ref. The
 * recipe is a stable contract: anything that generates a ref externally must hash
 * its fields identically.
 */
final class ContentRef
{
    /**
     * @param  list<string>  $parts  canonical field values, already in fixed order
     */
    public function compute(array $parts): string
    {
        $normalized = array_map(
            static fn (string $part): string => (string) Normalizer::normalize(trim($part), Normalizer::FORM_C),
            $parts,
        );

        return hash('sha256', implode("\n", $normalized));
    }
}

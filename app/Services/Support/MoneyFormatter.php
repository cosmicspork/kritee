<?php

namespace App\Services\Support;

use Illuminate\Support\Number;

/**
 * Formats monetary amounts for display. Amounts are stored as decimal strings;
 * this is the single place they become currency text.
 */
final class MoneyFormatter
{
    public static function format(float|string|null $amount): string
    {
        return (string) Number::currency((float) ($amount ?? 0));
    }
}

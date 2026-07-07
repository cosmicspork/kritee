<?php

namespace App\Services\Support;

/**
 * Formats minute counts for display ("510" → "8h 30m"). Durations are stored
 * as integer minutes everywhere; this is the single place they become text.
 */
final class DurationFormatter
{
    public static function minutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($hours === 0) {
            return $remainder.'m';
        }

        if ($remainder === 0) {
            return $hours.'h';
        }

        return $hours.'h '.$remainder.'m';
    }
}

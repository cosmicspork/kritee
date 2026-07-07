<?php

use App\Services\Support\DurationFormatter;

test('durations format as hours and minutes', function (int $minutes, string $expected) {
    expect(DurationFormatter::minutes($minutes))->toBe($expected);
})->with([
    [0, '0m'],
    [45, '45m'],
    [60, '1h'],
    [90, '1h 30m'],
    [510, '8h 30m'],
    [600, '10h'],
]);

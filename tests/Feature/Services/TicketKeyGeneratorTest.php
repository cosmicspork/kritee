<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketKeyGenerator;

test('the first key starts the sequence at one', function () {
    expect(app(TicketKeyGenerator::class)->next())->toBe('TK-1');
});

test('it advances past the highest existing numeric suffix', function () {
    $creator = User::factory()->create();
    Ticket::factory()->for($creator, 'creator')->create(['key' => 'TK-7']);
    Ticket::factory()->for($creator, 'creator')->create(['key' => 'TK-42']);
    Ticket::factory()->for($creator, 'creator')->create(['key' => 'TK-13']);

    expect(app(TicketKeyGenerator::class)->next())->toBe('TK-43');
});

test('it generates a key not already taken when applied repeatedly', function () {
    $creator = User::factory()->create();
    $generator = app(TicketKeyGenerator::class);

    $first = $generator->next();
    Ticket::factory()->for($creator, 'creator')->create(['key' => $first]);

    $second = $generator->next();

    expect($second)->not->toBe($first)
        ->and(Ticket::query()->where('key', $second)->exists())->toBeFalse();
});

<?php

use App\Models\Client;
use App\Services\Support\SlugGenerator;

test('it slugifies the base string when no collision exists', function () {
    $slug = app(SlugGenerator::class)->unique(Client::class, 'Acme Widgets Co');

    expect($slug)->toBe('acme-widgets-co');
});

test('it appends an incrementing suffix on collisions', function () {
    Client::factory()->create(['slug' => 'acme']);
    Client::factory()->create(['slug' => 'acme-2']);

    $slug = app(SlugGenerator::class)->unique(Client::class, 'Acme');

    expect($slug)->toBe('acme-3');
});

test('the ignored key lets a record keep its own slug', function () {
    $client = Client::factory()->create(['slug' => 'acme']);

    $slug = app(SlugGenerator::class)->unique(
        Client::class,
        'Acme',
        ignoreKey: $client->getKey(),
    );

    expect($slug)->toBe('acme');
});

<?php

use App\Events\DomainEvent;

arch('the DomainEvent marker is an interface')
    ->expect(DomainEvent::class)
    ->toBeInterface();

arch('every domain event implements the marker')
    ->expect('App\Events')
    ->classes()
    ->toImplement(DomainEvent::class);

arch('services do not depend on domain events')
    ->expect('App\Services')
    ->not->toUse('App\Events');

arch('controllers do not depend on domain events')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Events');

arch('models do not depend on domain events')
    ->expect('App\Models')
    ->not->toUse('App\Events');

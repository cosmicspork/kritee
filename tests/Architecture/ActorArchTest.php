<?php

use App\Actors\Contracts\Actor;

arch('the Actor contract is an interface')
    ->expect(Actor::class)
    ->toBeInterface();

arch('every actor implements the Actor contract')
    ->expect('App\Actors')
    ->toImplement(Actor::class)
    ->ignoring('App\Actors\Contracts');

arch('actors are final')
    ->expect('App\Actors')
    ->toBeFinal()
    ->ignoring('App\Actors\Contracts');

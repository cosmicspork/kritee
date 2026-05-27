<?php

use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use Spatie\LaravelData\Data;

// First architecture tests: the Action write-surface contract from
// docs/ARCHITECTURE.md. Rules that have nothing to guard yet join as the code
// they police lands — the service-layer rules (final, no transactions/events/
// action imports) with the first service in step 6, the domain-event rule when
// App\Events gains a class, and the actor carry-through rule in step 4.

arch('the Action contract is an interface')
    ->expect(Action::class)
    ->toBeInterface();

arch('ActionInput is a laravel-data DTO')
    ->expect(ActionInput::class)
    ->toExtend(Data::class);

arch('ActionResult is final')
    ->expect(ActionResult::class)
    ->toBeFinal();

arch('every action implements the Action contract')
    ->expect('App\Actions')
    ->toImplement(Action::class)
    ->ignoring(['App\Actions\Contracts', 'App\Actions\Fortify']);

arch('controllers do not depend on models')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models');

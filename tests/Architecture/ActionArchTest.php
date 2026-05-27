<?php

use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use Spatie\LaravelData\Data;

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

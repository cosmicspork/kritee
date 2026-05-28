<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

arch('services are final')
    ->expect('App\Services')
    ->toBeFinal()
    ->ignoring('App\Services\Contracts');

arch('services do not open transactions')
    ->expect('App\Services')
    ->not->toUse(DB::class);

arch('services do not dispatch events')
    ->expect('App\Services')
    ->not->toUse([Event::class, 'event']);

arch('services do not depend on actions')
    ->expect('App\Services')
    ->not->toUse('App\Actions');

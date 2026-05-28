<?php

namespace App\Http\Middleware;

use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the authenticated user as the request's {@see Actor}.
 */
class ResolveActor
{
    public function __construct(private readonly Application $app) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $this->app->instance(Actor::class, new UserActor($user));
        }

        return $next($request);
    }
}

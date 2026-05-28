<?php

namespace App\Providers;

use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The default actor follows the authenticated user when one exists, so
        // requests resolve a UserActor without depending on middleware order;
        // CLI, jobs, and agents override this binding with their own actor.
        $this->app->scoped(Actor::class, function (): Actor {
            $user = Auth::user();

            return $user instanceof User ? new UserActor($user) : new SystemActor;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

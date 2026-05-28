<?php

use App\Actors\AgentActor;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Models\AgentExecution;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/_test/actor', function () {
        $actor = app(Actor::class);

        return ['class' => $actor::class, 'id' => $actor->id()];
    });
});

test('an authenticated request resolves a UserActor for the current user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/_test/actor')
        ->assertOk()
        ->assertExactJson([
            'class' => UserActor::class,
            'id' => (string) $user->getKey(),
        ]);
});

test('a guest request falls back to the default SystemActor', function () {
    $this->get('/_test/actor')
        ->assertOk()
        ->assertExactJson([
            'class' => SystemActor::class,
            'id' => null,
        ]);
});

test('the actor defaults to SystemActor outside of HTTP', function () {
    expect(app(Actor::class))->toBeInstanceOf(SystemActor::class);
});

test('UserActor wraps a user', function () {
    $user = User::factory()->create();
    $actor = new UserActor($user);

    expect($actor->isUser())->toBeTrue()
        ->and($actor->isSystem())->toBeFalse()
        ->and($actor->isAgent())->toBeFalse()
        ->and($actor->id())->toBe((string) $user->getKey())
        ->and($actor->user())->toBe($user);
});

test('SystemActor has no user and no id', function () {
    $actor = new SystemActor;

    expect($actor->isSystem())->toBeTrue()
        ->and($actor->isUser())->toBeFalse()
        ->and($actor->isAgent())->toBeFalse()
        ->and($actor->id())->toBeNull();
});

test('AgentActor wraps an agent execution', function () {
    $execution = AgentExecution::factory()->create();
    $actor = new AgentActor($execution);

    expect($actor->isAgent())->toBeTrue()
        ->and($actor->isUser())->toBeFalse()
        ->and($actor->isSystem())->toBeFalse()
        ->and($actor->id())->toBe((string) $execution->getKey())
        ->and($actor->execution())->toBe($execution);
});

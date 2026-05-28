<?php

use App\Actions\Linkable\CreateLink;
use App\Actions\Linkable\CreateLinkInput;
use App\Actions\Linkable\RemoveLink;
use App\Actions\Linkable\RemoveLinkInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\LinkRelationshipType;
use App\Events\LinkRemoved;
use App\Models\Linkable;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function bindLinkUser(): User
{
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

/**
 * @return array{Ticket, Ticket}
 */
function linkedBlockingPair(): array
{
    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    return [$source, $target];
}

test('it removes the link and its inverse together', function () {
    bindLinkUser();

    [$source, $target] = linkedBlockingPair();
    expect(Linkable::count())->toBe(2);

    $result = app(RemoveLink::class)->execute(new RemoveLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    expect($result->success)->toBeTrue()
        ->and(Linkable::count())->toBe(0);
});

test('it removes a symmetric link with no inverse', function () {
    bindLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();
    $link = Linkable::factory()->create([
        'source_type' => $source->getMorphClass(),
        'source_id' => $source->getKey(),
        'target_type' => $target->getMorphClass(),
        'target_id' => $target->getKey(),
        'relationship_type' => LinkRelationshipType::RelatesTo,
    ]);

    $result = app(RemoveLink::class)->execute(new RemoveLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::RelatesTo,
    ));

    expect($result->success)->toBeTrue()
        ->and(Linkable::whereKey($link->getKey())->exists())->toBeFalse()
        ->and($result->data['inverse'])->toBeNull();
});

test('it fails when the link does not exist', function () {
    bindLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    $result = app(RemoveLink::class)->execute(new RemoveLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('link');
});

test('it denies removal when the actor is not a user', function () {
    bindLinkUser();
    [$source, $target] = linkedBlockingPair();

    app()->instance(Actor::class, new SystemActor);

    $result = app(RemoveLink::class)->execute(new RemoveLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and(Linkable::count())->toBe(2);
});

test('it dispatches LinkRemoved on success', function () {
    bindLinkUser();
    [$source, $target] = linkedBlockingPair();

    Event::fake();

    app(RemoveLink::class)->execute(new RemoveLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    Event::assertDispatched(LinkRemoved::class, function (LinkRemoved $event): bool {
        return $event->inverse !== null
            && $event->inverse['relationship_type'] === LinkRelationshipType::BlockedBy->value;
    });
});

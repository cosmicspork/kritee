<?php

use App\Actions\Linkable\CreateLink;
use App\Actions\Linkable\CreateLinkInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Enums\LinkRelationshipType;
use App\Events\LinkCreated;
use App\Models\Linkable;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function actAsLinkUser(): User
{
    $user = User::factory()->create();
    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it creates a single link for a symmetric relationship', function () {
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Project::factory()->create();

    $result = app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::RelatesTo,
    ));

    expect($result->success)->toBeTrue()
        ->and(Linkable::count())->toBe(1)
        ->and($result->data['inverse'])->toBeNull();
});

test('blocks auto-creates the blocked_by inverse in one transaction', function () {
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    $result = app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    expect($result->success)->toBeTrue()
        ->and(Linkable::count())->toBe(2);

    expect(Linkable::query()
        ->where('source_type', $target->getMorphClass())
        ->where('source_id', $target->getKey())
        ->where('target_type', $source->getMorphClass())
        ->where('target_id', $source->getKey())
        ->where('relationship_type', LinkRelationshipType::BlockedBy)
        ->exists())->toBeTrue();
});

test('duplicates auto-creates the duplicated_by inverse', function () {
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Duplicates,
    ));

    expect(Linkable::query()
        ->where('source_id', $target->getKey())
        ->where('target_id', $source->getKey())
        ->where('relationship_type', LinkRelationshipType::DuplicatedBy)
        ->exists())->toBeTrue();
});

test('it denies creation when the actor is not a user', function () {
    app()->instance(Actor::class, new SystemActor);

    $source = Ticket::factory()->create();
    $target = Project::factory()->create();

    $result = app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::RelatesTo,
    ));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor')
        ->and(Linkable::count())->toBe(0);
});

test('it rejects an unknown relationship type during input construction', function () {
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Project::factory()->create();

    expect(fn () => CreateLinkInput::validateAndCreate([
        'source_type' => $source->getMorphClass(),
        'source_id' => $source->getKey(),
        'target_type' => $target->getMorphClass(),
        'target_id' => $target->getKey(),
        'relationship_type' => 'not_a_real_type',
    ]))->toThrow(ValidationException::class);
});

test('a repeated idempotency key creates the link only once', function () {
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    $input = fn () => new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
        idempotencyKey: 'link-once',
    );

    $first = app(CreateLink::class)->execute($input());
    $second = app(CreateLink::class)->execute($input());

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and(Linkable::count())->toBe(2);
});

test('it dispatches LinkCreated on success', function () {
    Event::fake();
    actAsLinkUser();

    $source = Ticket::factory()->create();
    $target = Ticket::factory()->create();

    app(CreateLink::class)->execute(new CreateLinkInput(
        sourceType: $source->getMorphClass(),
        sourceId: $source->getKey(),
        targetType: $target->getMorphClass(),
        targetId: $target->getKey(),
        relationshipType: LinkRelationshipType::Blocks,
    ));

    Event::assertDispatched(LinkCreated::class, function (LinkCreated $event): bool {
        return $event->inverse !== null
            && $event->inverse->relationship_type === LinkRelationshipType::BlockedBy;
    });
});

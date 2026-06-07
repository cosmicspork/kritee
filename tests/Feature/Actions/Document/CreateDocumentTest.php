<?php

use App\Actions\Document\CreateDocument;
use App\Actions\Document\CreateDocumentInput;
use App\Actors\Contracts\Actor;
use App\Actors\SystemActor;
use App\Actors\UserActor;
use App\Events\DocumentCreated;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

function createDocumentAs(?User $user = null): User
{
    $user ??= User::factory()->create();

    app()->instance(Actor::class, new UserActor($user));

    return $user;
}

test('it creates a document for the acting user', function () {
    $user = createDocumentAs();
    $client = Client::factory()->create();

    $result = app(CreateDocument::class)->execute(CreateDocumentInput::validateAndCreate([
        'uploaded_by' => $user->getKey(),
        'title' => 'Onboarding notes',
        'content' => 'Kickoff agenda and contacts.',
        'client_id' => $client->getKey(),
    ]));

    expect($result->success)->toBeTrue()
        ->and($result->data)->toBeInstanceOf(Document::class)
        ->and($result->data->title)->toBe('Onboarding notes');

    $this->assertDatabaseHas('documents', [
        'uploaded_by' => $user->getKey(),
        'client_id' => $client->getKey(),
        'title' => 'Onboarding notes',
        'content' => 'Kickoff agenda and contacts.',
    ]);
});

test('it persists an attached file as a morphed attachment', function () {
    $user = createDocumentAs();

    $result = app(CreateDocument::class)->execute(CreateDocumentInput::validateAndCreate([
        'uploaded_by' => $user->getKey(),
        'title' => 'Contract',
        'file' => [
            'filename' => 'contract.pdf',
            'path' => 'documents/abc/contract.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4096,
        ],
    ]));

    expect($result->success)->toBeTrue();

    $attachment = Attachment::query()
        ->where('attachable_type', (new Document)->getMorphClass())
        ->where('attachable_id', $result->data->getKey())
        ->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->filename)->toBe('contract.pdf')
        ->and($attachment->uploaded_by)->toBe($user->getKey());
});

test('it dispatches DocumentCreated on success', function () {
    Event::fake();

    $user = createDocumentAs();

    app(CreateDocument::class)->execute(CreateDocumentInput::validateAndCreate([
        'uploaded_by' => $user->getKey(),
        'title' => 'Meeting recap',
    ]));

    Event::assertDispatched(DocumentCreated::class, function (DocumentCreated $event) use ($user) {
        return $event->document instanceof Document
            && $event->actorId === (string) $user->getKey();
    });
});

test('a non-user actor cannot create a document', function () {
    app()->instance(Actor::class, new SystemActor);
    $user = User::factory()->create();

    $result = app(CreateDocument::class)->execute(CreateDocumentInput::validateAndCreate([
        'uploaded_by' => $user->getKey(),
        'title' => 'System note',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('actor');

    $this->assertDatabaseEmpty('documents');
});

test('it rejects a missing title before any side effects', function () {
    createDocumentAs();

    expect(fn () => CreateDocumentInput::validateAndCreate([
        'uploaded_by' => 1,
        'title' => '',
    ]))->toThrow(ValidationException::class);
});

test('a repeated idempotency key does not create a second document', function () {
    $user = createDocumentAs();

    $input = fn () => CreateDocumentInput::validateAndCreate([
        'uploaded_by' => $user->getKey(),
        'title' => 'Imported doc',
        'idempotency_key' => 'document-import-3',
    ]);

    $first = app(CreateDocument::class)->execute($input());
    $second = app(CreateDocument::class)->execute($input());

    expect($first->success)->toBeTrue()
        ->and($second->success)->toBeTrue()
        ->and($second->data->getKey())->toBe($first->data->getKey())
        ->and(Document::count())->toBe(1);
});

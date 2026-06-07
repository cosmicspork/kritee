<?php

use App\Actions\Document\UpdateDocument;
use App\Actions\Document\UpdateDocumentInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

function updateDocumentAs(User $user): void
{
    app()->instance(Actor::class, new UserActor($user));
}

test('the uploader may update a document', function () {
    $owner = User::factory()->create();
    updateDocumentAs($owner);

    $document = Document::factory()->create([
        'uploaded_by' => $owner->getKey(),
        'title' => 'Draft',
        'content' => 'Original body.',
    ]);

    $result = app(UpdateDocument::class)->execute(UpdateDocumentInput::validateAndCreate([
        'document_id' => $document->getKey(),
        'title' => 'Final',
    ]));

    expect($result->success)->toBeTrue();

    $this->assertDatabaseHas('documents', [
        'id' => $document->getKey(),
        'title' => 'Final',
        'content' => 'Original body.',
    ]);
});

test('a non-owner non-admin may not update a document', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->create([
        'uploaded_by' => $owner->getKey(),
        'title' => 'Draft',
    ]);

    updateDocumentAs(User::factory()->create());

    $result = app(UpdateDocument::class)->execute(UpdateDocumentInput::validateAndCreate([
        'document_id' => $document->getKey(),
        'title' => 'Hijacked',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('documents', [
        'id' => $document->getKey(),
        'title' => 'Draft',
    ]);
});

test('an admin may update any document', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->create([
        'uploaded_by' => $owner->getKey(),
        'title' => 'Draft',
    ]);

    updateDocumentAs(User::factory()->create(['role' => UserRole::Admin]));

    $result = app(UpdateDocument::class)->execute(UpdateDocumentInput::validateAndCreate([
        'document_id' => $document->getKey(),
        'title' => 'Approved',
    ]));

    expect($result->success)->toBeTrue();

    $this->assertDatabaseHas('documents', [
        'id' => $document->getKey(),
        'title' => 'Approved',
    ]);
});

test('it fails when the document does not exist', function () {
    updateDocumentAs(User::factory()->create());

    $result = app(UpdateDocument::class)->execute(UpdateDocumentInput::validateAndCreate([
        'document_id' => 9999,
        'title' => 'Nope',
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('document_id');
});

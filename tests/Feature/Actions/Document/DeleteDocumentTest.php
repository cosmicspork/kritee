<?php

use App\Actions\Document\DeleteDocument;
use App\Actions\Document\DeleteDocumentInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\User;

function deleteDocumentAs(User $user): void
{
    app()->instance(Actor::class, new UserActor($user));
}

test('the uploader may delete a document and its attachments', function () {
    $owner = User::factory()->create();
    deleteDocumentAs($owner);

    $document = Document::factory()->create(['uploaded_by' => $owner->getKey()]);
    $document->attachments()->create([
        'uploaded_by' => $owner->getKey(),
        'filename' => 'file.pdf',
        'path' => 'documents/x/file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 10,
    ]);

    $result = app(DeleteDocument::class)->execute(DeleteDocumentInput::validateAndCreate([
        'document_id' => $document->getKey(),
    ]));

    expect($result->success)->toBeTrue();

    $this->assertDatabaseMissing('documents', ['id' => $document->getKey()]);
    expect(Attachment::query()
        ->where('attachable_type', (new Document)->getMorphClass())
        ->where('attachable_id', $document->getKey())
        ->count())->toBe(0);
});

test('a non-owner non-admin may not delete a document', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->create(['uploaded_by' => $owner->getKey()]);

    deleteDocumentAs(User::factory()->create());

    $result = app(DeleteDocument::class)->execute(DeleteDocumentInput::validateAndCreate([
        'document_id' => $document->getKey(),
    ]));

    expect($result->success)->toBeFalse()
        ->and($result->errors)->toHaveKey('authorization');

    $this->assertDatabaseHas('documents', ['id' => $document->getKey()]);
});

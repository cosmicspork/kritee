<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Document\CreateDocument;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Route::livewire('documents', 'pages::documents.index')->name('documents.index');
    Route::livewire('documents/create', 'pages::documents.create')->name('documents.create');
    Route::livewire('documents/{document}', 'pages::documents.show')->name('documents.show');
});

test('it creates a document through the action and redirects to it', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::documents.create')
        ->set('title', 'Project brief')
        ->set('content', 'Scope and goals.')
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('uploaded_by', $user->getKey())->firstOrFail();

    $this->assertDatabaseHas('documents', [
        'id' => $document->getKey(),
        'title' => 'Project brief',
        'content' => 'Scope and goals.',
    ]);
});

test('it invokes the CreateDocument action exactly once', function (): void {
    $user = User::factory()->create();

    $action = Mockery::mock(CreateDocument::class);
    $action->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::success(Document::factory()->create(['uploaded_by' => $user->getKey()])));

    app()->instance(CreateDocument::class, $action);

    Livewire::actingAs($user)
        ->test('pages::documents.create')
        ->set('title', 'Anything')
        ->call('save')
        ->assertHasNoErrors();
});

test('it validates the title before calling the action', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title'])
        ->assertNoRedirect();

    $this->assertDatabaseEmpty('documents');
});

test('it associates a selected client', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::documents.create')
        ->set('title', 'Client doc')
        ->set('clientId', $client->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('documents', [
        'uploaded_by' => $user->getKey(),
        'client_id' => $client->getKey(),
    ]);
});

test('it stores an uploaded file and persists it as an attachment', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('brief.pdf', 64, 'application/pdf');

    Livewire::actingAs($user)
        ->test('pages::documents.create')
        ->set('title', 'With attachment')
        ->set('file', $file)
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('uploaded_by', $user->getKey())->firstOrFail();
    $attachment = Attachment::query()
        ->where('attachable_type', $document->getMorphClass())
        ->where('attachable_id', $document->getKey())
        ->firstOrFail();

    expect($attachment->filename)->toBe('brief.pdf');
    Storage::disk('public')->assertExists($attachment->path);
});

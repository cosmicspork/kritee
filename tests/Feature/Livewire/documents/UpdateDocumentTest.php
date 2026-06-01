<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    Route::livewire('documents', 'pages::documents.index')->name('documents.index');
    Route::livewire('documents/{document}', 'pages::documents.show')->name('documents.show');
    Route::livewire('documents/{document}/edit', 'pages::documents.edit')->name('documents.edit');
});

test('the uploader can edit their document', function (): void {
    $owner = User::factory()->create();
    $document = Document::factory()->create([
        'uploaded_by' => $owner->getKey(),
        'title' => 'Before',
    ]);

    Livewire::actingAs($owner)
        ->test('pages::documents.edit', ['document' => $document])
        ->set('title', 'After')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('documents.show', $document->getKey()));

    $this->assertDatabaseHas('documents', [
        'id' => $document->getKey(),
        'title' => 'After',
    ]);
});

test('a non-owner cannot open the edit page', function (): void {
    $document = Document::factory()->create(['uploaded_by' => User::factory()->create()->getKey()]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.edit', ['document' => $document])
        ->assertForbidden();
});

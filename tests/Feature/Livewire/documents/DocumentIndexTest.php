<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    Route::livewire('documents', 'pages::documents.index')->name('documents.index');
    Route::livewire('documents/create', 'pages::documents.create')->name('documents.create');
    Route::livewire('documents/{document}', 'pages::documents.show')->name('documents.show');
    Route::livewire('documents/{document}/edit', 'pages::documents.edit')->name('documents.edit');
});

test('it lists documents to any authenticated user', function (): void {
    Document::factory()->create(['title' => 'Shared brief']);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.index')
        ->assertSee('Shared brief');
});

test('it filters documents by title', function (): void {
    Document::factory()->create(['title' => 'Quarterly report']);
    Document::factory()->create(['title' => 'Random memo']);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.index')
        ->set('search', 'Quarterly')
        ->assertSee('Quarterly report')
        ->assertDontSee('Random memo');
});

test('the uploader can delete their document', function (): void {
    $owner = User::factory()->create();
    $document = Document::factory()->create(['uploaded_by' => $owner->getKey()]);

    Livewire::actingAs($owner)
        ->test('pages::documents.index')
        ->call('delete', $document->getKey())
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('documents', ['id' => $document->getKey()]);
});

test('a non-owner does not see edit or delete controls', function (): void {
    $owner = User::factory()->create();
    $document = Document::factory()->create(['uploaded_by' => $owner->getKey()]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.index')
        ->assertDontSee('edit-document-'.$document->getKey())
        ->assertDontSee('delete-document-'.$document->getKey());
});

test('a non-owner deletion attempt is rejected by the action', function (): void {
    $owner = User::factory()->create();
    $document = Document::factory()->create(['uploaded_by' => $owner->getKey()]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.index')
        ->call('delete', $document->getKey());

    $this->assertDatabaseHas('documents', ['id' => $document->getKey()]);
});

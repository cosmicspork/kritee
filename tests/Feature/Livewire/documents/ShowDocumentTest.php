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

test('it renders the document title and content', function (): void {
    $document = Document::factory()->create([
        'title' => 'Strategy memo',
        'content' => 'The plan for next quarter.',
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.show', ['document' => $document])
        ->assertSee('Strategy memo')
        ->assertSee('The plan for next quarter.');
});

test('it links an attached file', function (): void {
    $document = Document::factory()->create();
    $document->attachments()->create([
        'uploaded_by' => $document->uploaded_by,
        'filename' => 'appendix.pdf',
        'path' => 'documents/y/appendix.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 20,
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::documents.show', ['document' => $document])
        ->assertSee('appendix.pdf');
});

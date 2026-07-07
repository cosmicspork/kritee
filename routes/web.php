<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::livewire('invitations/{token}', 'pages::invitations.accept')
    ->middleware('guest')
    ->name('invitations.accept');

Route::livewire('roadmap', 'pages::roadmaps.public.index')->name('roadmap.index');
Route::livewire('roadmap/{roadmap}', 'pages::roadmaps.public.show')->name('roadmap.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');

    Route::livewire('clients', 'pages::clients.index')->name('clients.index');
    Route::livewire('clients/{client}', 'pages::clients.show')->name('clients.show');

    Route::livewire('projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('projects/create', 'pages::projects.create')->name('projects.create');
    Route::livewire('projects/{project}/edit', 'pages::projects.edit')->name('projects.edit');

    Route::livewire('tickets', 'pages::tickets.board')->name('tickets.board');
    Route::livewire('tickets/list', 'pages::tickets.index')->name('tickets.index');

    Route::livewire('time', 'pages::time.index')->name('time.index');

    Route::livewire('expenses', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('expenses/create', 'pages::expenses.create')->name('expenses.create');
    Route::livewire('expenses/{expense}/edit', 'pages::expenses.edit')->name('expenses.edit');

    Route::livewire('invoices', 'pages::invoices.index')->name('invoices.index');
    Route::livewire('invoices/create', 'pages::invoices.create')->name('invoices.create');
    Route::livewire('invoices/{invoice}', 'pages::invoices.show')->name('invoices.show');

    Route::livewire('roadmaps', 'pages::roadmaps.index')->name('roadmaps.index');
    Route::livewire('roadmaps/{roadmap}', 'pages::roadmaps.show')->name('roadmaps.show');

    Route::livewire('documents', 'pages::documents.index')->name('documents.index');
    Route::livewire('documents/create', 'pages::documents.create')->name('documents.create');
    Route::livewire('documents/{document}', 'pages::documents.show')->name('documents.show');
    Route::livewire('documents/{document}/edit', 'pages::documents.edit')->name('documents.edit');
});

require __DIR__.'/settings.php';

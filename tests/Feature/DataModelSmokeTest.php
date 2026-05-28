<?php

use App\Models\Attachment;
use App\Models\BillingRate;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\Linkable;
use App\Models\Project;
use App\Models\ReviewQueueItem;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use App\Models\Ticket;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Model;

dataset('models', [
    'Client' => [Client::class, 'clients'],
    'Contact' => [Contact::class, 'contacts'],
    'Project' => [Project::class, 'projects'],
    'Roadmap' => [Roadmap::class, 'roadmaps'],
    'RoadmapItem' => [RoadmapItem::class, 'roadmap_items'],
    'Document' => [Document::class, 'documents'],
    'Ticket' => [Ticket::class, 'tickets'],
    'Comment' => [Comment::class, 'comments'],
    'Attachment' => [Attachment::class, 'attachments'],
    'TimeEntry' => [TimeEntry::class, 'time_entries'],
    'Expense' => [Expense::class, 'expenses'],
    'Invoice' => [Invoice::class, 'invoices'],
    'LineItem' => [LineItem::class, 'line_items'],
    'BillingRate' => [BillingRate::class, 'billing_rates'],
    'Linkable' => [Linkable::class, 'linkables'],
    'ReviewQueueItem' => [ReviewQueueItem::class, 'review_queue_items'],
]);

test('factory persists the model', function (string $model, string $table) {
    /** @var Model $instance */
    $instance = $model::factory()->create();

    expect($instance->exists)->toBeTrue();
    $this->assertDatabaseHas($table, ['id' => $instance->getKey()]);
})->with('models');

test('a client loads its contacts', function () {
    $client = Client::factory()->create();
    Contact::factory()->count(2)->for($client)->create();

    expect($client->contacts()->count())->toBe(2)
        ->and($client->contacts->first())->toBeInstanceOf(Contact::class);
});

test('a ticket loads its associated projects', function () {
    $ticket = Ticket::factory()->create();
    $projects = Project::factory()->count(2)->create();
    $ticket->projects()->attach($projects);

    $ticket->refresh();

    expect($ticket->projects)->toHaveCount(2)
        ->and($ticket->projects->first())->toBeInstanceOf(Project::class);
});

test('an invoice loads its line items', function () {
    $invoice = Invoice::factory()->create();
    LineItem::factory()->count(3)->for($invoice)->create();

    expect($invoice->lineItems)->toHaveCount(3)
        ->and($invoice->lineItems->first())->toBeInstanceOf(LineItem::class);
});

test('a roadmap loads its items', function () {
    $roadmap = Roadmap::factory()->create();
    RoadmapItem::factory()->count(2)->for($roadmap)->create();

    expect($roadmap->items)->toHaveCount(2)
        ->and($roadmap->items->first())->toBeInstanceOf(RoadmapItem::class);
});

test('a polymorphic billing rate resolves its rateable', function () {
    $client = Client::factory()->create();
    $rate = BillingRate::factory()->create([
        'rateable_type' => $client->getMorphClass(),
        'rateable_id' => $client->getKey(),
    ]);

    expect($rate->rateable)->toBeInstanceOf(Client::class)
        ->and($rate->rateable->is($client))->toBeTrue();
});

test('a linkable resolves both morph ends', function () {
    $link = Linkable::factory()->create();

    expect($link->source)->not->toBeNull()
        ->and($link->target)->not->toBeNull();
});

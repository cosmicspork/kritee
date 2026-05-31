<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Ticket\CreateTicket;
use App\Actions\Ticket\CreateTicketInput;
use App\Actions\Ticket\UpdateTicket;
use App\Actions\Ticket\UpdateTicketInput;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the list page renders the existing tickets', function () {
    Ticket::factory()->create(['title' => 'Investigate billing drift']);

    Livewire::test('pages::tickets.index')
        ->assertOk()
        ->assertSee('Investigate billing drift');
});

test('saving a new ticket invokes CreateTicket with the form values', function () {
    $ticket = Ticket::factory()->make();

    $spy = Mockery::mock(CreateTicket::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(fn (CreateTicketInput $input): bool => $input->title === 'Wire the kanban board'
            && $input->priority === TicketPriority::High
            && $input->status === TicketStatus::Open)
        ->andReturn(ActionResult::success($ticket));
    app()->instance(CreateTicket::class, $spy);

    Livewire::test('pages::tickets.index')
        ->call('create')
        ->assertSet('showForm', true)
        ->set('title', 'Wire the kanban board')
        ->set('priority', TicketPriority::High->value)
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();
});

test('saving through the real action persists the ticket', function () {
    Livewire::test('pages::tickets.index')
        ->call('create')
        ->set('title', 'Persisted ticket')
        ->set('priority', TicketPriority::Medium->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', [
        'title' => 'Persisted ticket',
        'creator_id' => $this->user->getKey(),
    ]);
});

test('editing a ticket invokes UpdateTicket', function () {
    $ticket = Ticket::factory()->create([
        'creator_id' => $this->user->getKey(),
        'title' => 'Old title',
    ]);

    $spy = Mockery::mock(UpdateTicket::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(fn (UpdateTicketInput $input): bool => $input->ticketId === $ticket->getKey()
            && $input->title === 'New title')
        ->andReturn(ActionResult::success($ticket));
    app()->instance(UpdateTicket::class, $spy);

    Livewire::test('pages::tickets.index')
        ->call('edit', $ticket->getKey())
        ->assertSet('editingId', $ticket->getKey())
        ->set('title', 'New title')
        ->call('save')
        ->assertHasNoErrors();
});

test('an action failure surfaces field errors and keeps the form open', function () {
    $spy = Mockery::mock(CreateTicket::class);
    $spy->shouldReceive('execute')
        ->once()
        ->andReturn(ActionResult::failure(['title' => 'The title is required.']));
    app()->instance(CreateTicket::class, $spy);

    Livewire::test('pages::tickets.index')
        ->call('create')
        ->set('title', 'Anything')
        ->call('save')
        ->assertHasErrors('title')
        ->assertSet('showForm', true);
});

test('the status filter narrows the listing', function () {
    Ticket::factory()->create(['title' => 'Open work', 'status' => TicketStatus::Open]);
    Ticket::factory()->create(['title' => 'Finished work', 'status' => TicketStatus::Done]);

    Livewire::test('pages::tickets.index')
        ->set('statusFilter', TicketStatus::Done->value)
        ->assertSee('Finished work')
        ->assertDontSee('Open work');
});

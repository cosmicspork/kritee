<?php

use App\Actions\Comment\AddComment;
use App\Actions\Comment\AddCommentInput;
use App\Actions\Contracts\ActionResult;
use App\Actions\Ticket\MoveTicket;
use App\Actions\Ticket\MoveTicketInput;
use App\Actions\Ticket\SetTicketBlocked;
use App\Actions\Ticket\SetTicketBlockedInput;
use App\Enums\TicketStatus;
use App\Events\TicketMoved;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    actAsUser($this->user);
});

test('the board renders a column for every status', function () {
    Ticket::factory()->create(['title' => 'Card on the board', 'status' => TicketStatus::Open]);

    $component = Livewire::test('pages::tickets.board')->assertOk();

    foreach (TicketStatus::cases() as $status) {
        $component->assertSee($status->label());
    }

    $component->assertSee('Card on the board');
});

test('a drop invokes MoveTicket with the target column and position', function () {
    $ticket = Ticket::factory()->create([
        'creator_id' => $this->user->getKey(),
        'status' => TicketStatus::Open,
    ]);

    $spy = Mockery::mock(MoveTicket::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(fn (MoveTicketInput $input): bool => $input->ticketId === $ticket->getKey()
            && $input->status === TicketStatus::InProgress
            && $input->sortOrder === 2)
        ->andReturn(ActionResult::success($ticket));
    app()->instance(MoveTicket::class, $spy);

    Livewire::test('pages::tickets.board')
        ->call('moveTicket', $ticket->getKey(), TicketStatus::InProgress->value, 2)
        ->assertHasNoErrors();
});

test('a drop through the real action persists the status transition', function () {
    Event::fake([TicketMoved::class]);

    $ticket = Ticket::factory()->create([
        'creator_id' => $this->user->getKey(),
        'status' => TicketStatus::Open,
        'sort_order' => 0,
    ]);

    Livewire::test('pages::tickets.board')
        ->call('moveTicket', $ticket->getKey(), TicketStatus::InProgress->value, 0)
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);

    Event::assertDispatched(TicketMoved::class);
});

test('toggling blocked invokes SetTicketBlocked with the negated flag', function () {
    $ticket = Ticket::factory()->create([
        'creator_id' => $this->user->getKey(),
        'is_blocked' => false,
    ]);

    $spy = Mockery::mock(SetTicketBlocked::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(fn (SetTicketBlockedInput $input): bool => $input->ticketId === $ticket->getKey()
            && $input->isBlocked === true)
        ->andReturn(ActionResult::success($ticket));
    app()->instance(SetTicketBlocked::class, $spy);

    Livewire::test('pages::tickets.board')
        ->call('toggleBlocked', $ticket->getKey())
        ->assertHasNoErrors();
});

test('a blocked ticket renders with the error outline class', function () {
    Ticket::factory()->create([
        'title' => 'Stuck on access',
        'is_blocked' => true,
    ]);

    Livewire::test('pages::tickets.board')
        ->assertSee('border-error');
});

test('creating a ticket from a column seeds that column status', function () {
    Livewire::test('pages::tickets.board')
        ->call('create', TicketStatus::InReview->value)
        ->assertSet('status', TicketStatus::InReview->value)
        ->set('title', 'Drafted in review')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', [
        'title' => 'Drafted in review',
        'status' => TicketStatus::InReview->value,
        'creator_id' => $this->user->getKey(),
    ]);
});

test('adding a comment from the detail drawer invokes AddComment', function () {
    $ticket = Ticket::factory()->create(['creator_id' => $this->user->getKey()]);
    $comment = Comment::factory()->make();

    $spy = Mockery::mock(AddComment::class);
    $spy->shouldReceive('execute')
        ->once()
        ->withArgs(fn (AddCommentInput $input): bool => $input->ticketId === $ticket->getKey()
            && $input->content === 'Looks ready to ship.')
        ->andReturn(ActionResult::success($comment));
    app()->instance(AddComment::class, $spy);

    Livewire::test('pages::tickets.board')
        ->call('openDetail', $ticket->getKey())
        ->assertSet('showDetail', true)
        ->set('comment', 'Looks ready to ship.')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSet('comment', '');
});

test('an empty comment surfaces a validation error', function () {
    $ticket = Ticket::factory()->create(['creator_id' => $this->user->getKey()]);

    Livewire::test('pages::tickets.board')
        ->call('openDetail', $ticket->getKey())
        ->set('comment', '')
        ->call('addComment')
        ->assertHasErrors(['comment']);
});

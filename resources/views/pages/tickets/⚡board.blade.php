<?php

use App\Actions\Comment\AddComment;
use App\Actions\Comment\AddCommentInput;
use App\Actions\Ticket\CreateTicket;
use App\Actions\Ticket\CreateTicketInput;
use App\Actions\Ticket\MoveTicket;
use App\Actions\Ticket\MoveTicketInput;
use App\Actions\Ticket\SetTicketBlocked;
use App\Actions\Ticket\SetTicketBlockedInput;
use App\Actions\Ticket\UpdateTicket;
use App\Actions\Ticket\UpdateTicketInput;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Board')] class extends Component {
    use Toast;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public ?string $description = null;

    public string $status = '';

    public string $priority = '';

    public ?int $clientId = null;

    public ?int $assigneeId = null;

    public ?string $dueDate = null;

    public bool $showDetail = false;

    public ?int $detailId = null;

    public string $comment = '';

    /**
     * Tickets grouped into one collection per status column, ordered for the board.
     *
     * @return array<string, \Illuminate\Support\Collection<int, Ticket>>
     */
    #[Computed]
    public function columns(): array
    {
        $tickets = Ticket::query()
            ->with(['client', 'assignee'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Ticket $ticket): string => $ticket->status->value);

        $columns = [];

        foreach (TicketStatus::cases() as $status) {
            $columns[$status->value] = $tickets->get($status->value, collect());
        }

        return $columns;
    }

    /**
     * The ticket shown in the detail drawer, with its comments eager loaded.
     */
    #[Computed]
    public function detailTicket(): ?Ticket
    {
        if ($this->detailId === null) {
            return null;
        }

        return Ticket::query()
            ->with(['comments.author', 'client', 'assignee'])
            ->find($this->detailId);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            fn (TicketStatus $status): array => ['id' => $status->value, 'name' => $status->label()],
            TicketStatus::cases(),
        );
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function priorityOptions(): array
    {
        return array_map(
            fn (TicketPriority $priority): array => ['id' => $priority->value, 'name' => $priority->label()],
            TicketPriority::cases(),
        );
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return Client::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function assigneeOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    public function canCreate(): bool
    {
        return Auth::user()->can('create', Ticket::class);
    }

    /**
     * Persist a drag-and-drop drop through {@see MoveTicket}: the target column
     * is the status of the list the card landed in, and the position is its new
     * index within that list. The action owns reordering and the unit of work.
     */
    public function moveTicket(int $ticketId, string $status, int $position): void
    {
        $target = TicketStatus::tryFrom($status);

        if ($target === null) {
            $this->error(__('Unknown column.'));

            return;
        }

        $result = app(MoveTicket::class)->execute(new MoveTicketInput(
            ticketId: $ticketId,
            status: $target,
            sortOrder: max($position, 0),
        ));

        unset($this->columns);

        if (! $result->success) {
            $this->error(reset($result->errors) ?: __('Could not move the ticket.'));
        }
    }

    /**
     * Flip the blocked flag through {@see SetTicketBlocked}.
     */
    public function toggleBlocked(int $ticketId): void
    {
        $ticket = Ticket::findOrFail($ticketId);

        $result = app(SetTicketBlocked::class)->execute(new SetTicketBlockedInput(
            ticketId: $ticketId,
            isBlocked: ! $ticket->is_blocked,
        ));

        unset($this->columns, $this->detailTicket);

        if (! $result->success) {
            $this->error(reset($result->errors) ?: __('Could not update the ticket.'));
        }
    }

    /**
     * Open the form for a new ticket, seeding it with the column it was added from.
     */
    public function create(?string $status = null): void
    {
        $this->resetForm();
        $this->status = TicketStatus::tryFrom((string) $status)?->value ?? TicketStatus::Open->value;
        $this->showForm = true;
    }

    /**
     * Open the form prefilled with the given ticket's attributes.
     */
    public function edit(int $ticket): void
    {
        $record = Ticket::findOrFail($ticket);

        $this->editingId = $record->id;
        $this->title = $record->title;
        $this->description = $record->description;
        $this->status = $record->status->value;
        $this->priority = $record->priority->value;
        $this->clientId = $record->client_id;
        $this->assigneeId = $record->assignee_id;
        $this->dueDate = $record->due_date?->toDateString();

        $this->showDetail = false;
        $this->showForm = true;
    }

    /**
     * Persist the form through {@see CreateTicket} or {@see UpdateTicket}; those
     * actions own validation, authorization, and the unit of work.
     */
    public function save(): void
    {
        $result = $this->editingId === null
            ? app(CreateTicket::class)->execute(new CreateTicketInput(
                title: $this->title,
                description: $this->description !== '' ? $this->description : null,
                status: TicketStatus::from($this->status),
                priority: TicketPriority::from($this->priority),
                clientId: $this->clientId,
                assigneeId: $this->assigneeId,
                dueDate: $this->dueDate !== '' ? $this->dueDate : null,
            ))
            : app(UpdateTicket::class)->execute(new UpdateTicketInput(
                ticketId: $this->editingId,
                title: $this->title,
                description: $this->description !== '' ? $this->description : null,
                priority: TicketPriority::from($this->priority),
                clientId: $this->clientId,
                assigneeId: $this->assigneeId,
                dueDate: $this->dueDate !== '' ? $this->dueDate : null,
            ));

        if (! $result->success) {
            foreach ($result->errors as $field => $message) {
                $this->addError(is_string($field) ? $field : 'title', (string) $message);
            }

            $this->error(__('Could not save the ticket.'));

            return;
        }

        unset($this->columns);
        $this->showForm = false;
        $this->resetForm();

        $this->success(__('Ticket saved.'));
    }

    /**
     * Open the detail drawer for the given ticket.
     */
    public function openDetail(int $ticket): void
    {
        $this->detailId = $ticket;
        $this->reset('comment');
        $this->resetValidation();
        $this->showDetail = true;
    }

    /**
     * Post a comment on the ticket shown in the detail drawer through {@see AddComment}.
     */
    public function addComment(): void
    {
        if ($this->detailId === null) {
            return;
        }

        $this->validate([
            'comment' => ['required', 'string', 'max:10000'],
        ]);

        $result = app(AddComment::class)->execute(new AddCommentInput(
            ticketId: $this->detailId,
            content: $this->comment,
        ));

        if (! $result->success) {
            $this->error(reset($result->errors) ?: __('Could not add the comment.'));

            return;
        }

        $this->reset('comment');
        unset($this->detailTicket);

        $this->success(__('Comment added.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'title', 'description', 'clientId', 'assigneeId', 'dueDate');
        $this->resetValidation();
        $this->status = TicketStatus::Open->value;
        $this->priority = TicketPriority::Medium->value;
    }
}; ?>

<section class="w-full space-y-6">
    <x-header :title="__('Board')" :subtitle="__('Drag tickets between columns to update their status')" separator>
        <x-slot:actions>
            @if (\Illuminate\Support\Facades\Route::has('tickets.index'))
                <x-button
                    :label="__('List')"
                    icon="o-list-bullet"
                    :link="route('tickets.index')"
                    class="btn-ghost"
                    data-test="list-link"
                />
            @endif

            @if ($this->canCreate())
                <x-button
                    :label="__('New ticket')"
                    icon="o-plus"
                    wire:click="create"
                    class="btn-primary"
                    data-test="new-ticket-button"
                />
            @endif
        </x-slot:actions>
    </x-header>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($this->columns as $statusValue => $tickets)
            @php($column = \App\Enums\TicketStatus::from($statusValue))
            <div class="flex w-80 shrink-0 flex-col rounded-xl bg-base-100 p-3" wire:key="column-{{ $statusValue }}">
                <div class="mb-3 flex items-center justify-between px-1">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        {{ $column->label() }}
                        <x-badge :value="$tickets->count()" class="badge-soft badge-sm" />
                    </span>

                    @if ($this->canCreate())
                        <x-button
                            icon="o-plus"
                            wire:click="create('{{ $statusValue }}')"
                            class="btn-ghost btn-xs btn-circle"
                            data-test="add-to-{{ $statusValue }}"
                        />
                    @endif
                </div>

                <div
                    x-sort="$wire.moveTicket($item, '{{ $statusValue }}', $position)"
                    x-sort:group="board"
                    class="flex min-h-24 flex-col gap-2"
                    data-test="column-{{ $statusValue }}"
                >
                    @foreach ($tickets as $ticket)
                        <div
                            x-sort:item="{{ $ticket->id }}"
                            wire:key="card-{{ $ticket->id }}"
                            class="cursor-grab active:cursor-grabbing"
                        >
                            <x-card
                                class="{{ $ticket->is_blocked ? 'border-2 border-error' : 'border border-base-300' }} transition-shadow hover:shadow-md"
                                body-class="p-3 gap-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <button
                                        type="button"
                                        wire:click="openDetail({{ $ticket->id }})"
                                        class="text-left text-sm font-medium hover:underline"
                                        data-test="card-title-{{ $ticket->id }}"
                                    >
                                        {{ $ticket->title }}
                                    </button>
                                    <span class="font-mono text-xs text-base-content/50">{{ $ticket->key }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-1">
                                    <x-badge :value="$ticket->priority->label()" class="badge-soft badge-xs" />
                                    @if ($ticket->is_blocked)
                                        <x-badge :value="__('Blocked')" class="badge-error badge-xs" />
                                    @endif
                                    @if ($ticket->assignee)
                                        <x-badge :value="$ticket->assignee->name" class="badge-ghost badge-xs" />
                                    @endif
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-base-content/50">{{ $ticket->client?->name }}</span>
                                    <div class="flex items-center gap-1">
                                        <x-button
                                            icon="{{ $ticket->is_blocked ? 's-lock-closed' : 'o-lock-open' }}"
                                            wire:click="toggleBlocked({{ $ticket->id }})"
                                            class="btn-ghost btn-xs btn-circle {{ $ticket->is_blocked ? 'text-error' : '' }}"
                                            :tooltip="$ticket->is_blocked ? __('Unblock') : __('Block')"
                                            data-test="toggle-blocked-{{ $ticket->id }}"
                                        />
                                        <x-button
                                            icon="o-pencil-square"
                                            wire:click="edit({{ $ticket->id }})"
                                            class="btn-ghost btn-xs btn-circle"
                                            :tooltip="__('Edit')"
                                            data-test="edit-ticket-{{ $ticket->id }}"
                                        />
                                    </div>
                                </div>
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-modal wire:model="showForm" box-class="max-w-xl" :title="$editingId ? __('Edit ticket') : __('New ticket')" class="backdrop-blur">
        <x-form wire:submit="save">
            <x-input wire:model="title" :label="__('Title')" required data-test="ticket-title" />

            <x-textarea wire:model="description" :label="__('Description')" rows="4" data-test="ticket-description" />

            <div class="grid gap-4 sm:grid-cols-2">
                @if ($editingId === null)
                    <x-select
                        wire:model="status"
                        :label="__('Status')"
                        :options="$this->statusOptions"
                        data-test="ticket-status"
                    />
                @endif

                <x-select
                    wire:model="priority"
                    :label="__('Priority')"
                    :options="$this->priorityOptions"
                    data-test="ticket-priority"
                />

                <x-select
                    wire:model="clientId"
                    :label="__('Client')"
                    :options="$this->clientOptions"
                    :placeholder="__('Unassigned')"
                    data-test="ticket-client"
                />

                <x-select
                    wire:model="assigneeId"
                    :label="__('Assignee')"
                    :options="$this->assigneeOptions"
                    :placeholder="__('Unassigned')"
                    data-test="ticket-assignee"
                />

                <x-input wire:model="dueDate" :label="__('Due date')" type="date" data-test="ticket-due-date" />
            </div>

            <x-slot:actions>
                <x-button :label="__('Cancel')" wire:click="$set('showForm', false)" class="btn-ghost" />
                <x-button type="submit" :label="__('Save')" class="btn-primary" data-test="save-ticket-button" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-drawer wire:model="showDetail" right with-close-button class="w-full lg:w-1/3" :title="$this->detailTicket?->title">
        @if ($this->detailTicket)
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-sm text-base-content/50">{{ $this->detailTicket->key }}</span>
                    <x-badge :value="$this->detailTicket->status->label()" class="badge-soft badge-sm" />
                    <x-badge :value="$this->detailTicket->priority->label()" class="badge-soft badge-sm" />
                    @if ($this->detailTicket->is_blocked)
                        <x-badge :value="__('Blocked')" class="badge-error badge-sm" />
                    @endif
                </div>

                @if ($this->detailTicket->description)
                    <p class="whitespace-pre-line text-sm text-base-content/80">{{ $this->detailTicket->description }}</p>
                @endif

                <div class="flex items-center gap-2">
                    <x-button
                        :label="$this->detailTicket->is_blocked ? __('Unblock') : __('Block')"
                        wire:click="toggleBlocked({{ $this->detailTicket->id }})"
                        class="btn-sm {{ $this->detailTicket->is_blocked ? 'btn-ghost' : 'btn-outline btn-error' }}"
                        data-test="detail-toggle-blocked"
                    />
                    <x-button
                        :label="__('Edit')"
                        icon="o-pencil-square"
                        wire:click="edit({{ $this->detailTicket->id }})"
                        class="btn-ghost btn-sm"
                        data-test="detail-edit"
                    />
                </div>

                <div class="border-t border-base-300 pt-4">
                    <h3 class="mb-3 text-sm font-medium text-base-content/70">{{ __('Comments') }}</h3>

                    <div class="space-y-3">
                        @forelse ($this->detailTicket->comments as $comment)
                            <div class="rounded-lg bg-base-200 p-3" wire:key="comment-{{ $comment->id }}">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium">{{ $comment->author?->name }}</span>
                                    <span class="text-xs text-base-content/50">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="whitespace-pre-line text-sm">{{ $comment->content }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-base-content/50">{{ __('No comments yet.') }}</p>
                        @endforelse
                    </div>

                    <x-form wire:submit="addComment" class="mt-4">
                        <x-textarea
                            wire:model="comment"
                            :placeholder="__('Add a comment…')"
                            rows="3"
                            data-test="comment-content"
                        />

                        <x-slot:actions>
                            <x-button
                                type="submit"
                                :label="__('Comment')"
                                class="btn-primary btn-sm"
                                data-test="add-comment-button"
                            />
                        </x-slot:actions>
                    </x-form>
                </div>
            </div>
        @endif
    </x-drawer>
</section>

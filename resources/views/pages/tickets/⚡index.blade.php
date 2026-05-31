<?php

use App\Actions\Ticket\CreateTicket;
use App\Actions\Ticket\CreateTicketInput;
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

new #[Layout('layouts::app.sidebar'), Title('Tickets')] class extends Component {
    use Toast;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public ?string $description = null;

    public string $status = '';

    public string $priority = '';

    public ?int $clientId = null;

    public ?int $assigneeId = null;

    public ?string $dueDate = null;

    public function updatedSearch(): void
    {
        unset($this->tickets);
    }

    public function updatedStatusFilter(): void
    {
        unset($this->tickets);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Ticket>
     */
    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with(['client', 'assignee'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('key', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->get();
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
     * Open the form for a new ticket.
     */
    public function create(): void
    {
        $this->resetForm();
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

        unset($this->tickets);
        $this->showForm = false;
        $this->resetForm();

        $this->success(__('Ticket saved.'));
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
    <x-header :title="__('Tickets')" :subtitle="__('Track and triage client work')" separator>
        <x-slot:actions>
            @if (\Illuminate\Support\Facades\Route::has('tickets.board'))
                <x-button
                    :label="__('Board')"
                    icon="o-view-columns"
                    :link="route('tickets.board')"
                    class="btn-ghost"
                    data-test="board-link"
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

    <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
        <x-input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            icon="o-magnifying-glass"
            :placeholder="__('Search by title or key')"
            clearable
            data-test="tickets-search"
        />

        <x-select
            wire:model.live="statusFilter"
            :label="__('Status')"
            :options="$this->statusOptions"
            :placeholder="__('All statuses')"
            data-test="tickets-status-filter"
        />
    </div>

    @if ($this->tickets->isEmpty())
        <x-card>
            <p class="py-8 text-center text-base-content/60">{{ __('No tickets found.') }}</p>
        </x-card>
    @else
        <table class="table" data-test="tickets-table">
            <thead>
                <tr>
                    <th>{{ __('Key') }}</th>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Priority') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Assignee') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->tickets as $ticket)
                    <tr wire:key="ticket-{{ $ticket->id }}">
                        <td class="font-mono text-sm">{{ $ticket->key }}</td>
                        <td>
                            <span class="flex items-center gap-2">
                                {{ $ticket->title }}
                                @if ($ticket->is_blocked)
                                    <x-badge :value="__('Blocked')" class="badge-error badge-sm" />
                                @endif
                            </span>
                        </td>
                        <td><x-badge :value="$ticket->status->label()" class="badge-soft" /></td>
                        <td><x-badge :value="$ticket->priority->label()" class="badge-soft" /></td>
                        <td class="text-base-content/70">{{ $ticket->client?->name ?? '—' }}</td>
                        <td class="text-base-content/70">{{ $ticket->assignee?->name ?? '—' }}</td>
                        <td class="text-end">
                            <x-button
                                :label="__('Edit')"
                                wire:click="edit({{ $ticket->id }})"
                                class="btn-ghost btn-sm"
                                data-test="edit-ticket-{{ $ticket->id }}"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

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
</section>

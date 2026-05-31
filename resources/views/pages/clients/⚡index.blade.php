<?php

use App\Actions\Client\ArchiveClient;
use App\Actions\Client\ArchiveClientInput;
use App\Actions\Client\CreateClient;
use App\Actions\Client\CreateClientInput;
use App\Actions\Client\UpdateClient;
use App\Actions\Client\UpdateClientInput;
use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Clients')] class extends Component {
    use Toast;

    public bool $showFormModal = false;

    public ?int $editingClientId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';

    public string $search = '';
    public bool $showArchived = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Client>
     */
    #[Computed]
    public function clients()
    {
        return Client::query()
            ->when(! $this->showArchived, fn ($query) => $query->where('status', ClientStatus::Active))
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->withCount('contacts')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('Name')],
            ['key' => 'email', 'label' => __('Email')],
            ['key' => 'phone', 'label' => __('Phone')],
            ['key' => 'contacts_count', 'label' => __('Contacts')],
            ['key' => 'status', 'label' => __('Status')],
        ];
    }

    /**
     * Row link pattern for the table, or null until the detail route is wired.
     */
    #[Computed]
    public function rowLink(): ?string
    {
        return Route::has('clients.show')
            ? route('clients.show', ['client' => '[id]'])
            : null;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $clientId): void
    {
        $client = Client::findOrFail($clientId);

        $this->editingClientId = $client->id;
        $this->name = $client->name;
        $this->email = (string) $client->email;
        $this->phone = (string) $client->phone;
        $this->address = (string) $client->address;
        $this->notes = (string) $client->notes;

        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->editingClientId === null
            ? app(CreateClient::class)->execute(new CreateClientInput(
                name: $validated['name'],
                email: $this->nullable($validated['email']),
                phone: $this->nullable($validated['phone']),
                address: $this->nullable($validated['address']),
                notes: $this->nullable($validated['notes']),
            ))
            : app(UpdateClient::class)->execute(new UpdateClientInput(
                clientId: $this->editingClientId,
                name: $validated['name'],
                email: $this->nullable($validated['email']),
                phone: $this->nullable($validated['phone']),
                address: $this->nullable($validated['address']),
                notes: $this->nullable($validated['notes']),
            ));

        if (! $result->success) {
            $this->surfaceErrors($result->errors);

            return;
        }

        unset($this->clients);
        $this->showFormModal = false;

        $this->success($this->editingClientId === null
            ? __('Client created.')
            : __('Client updated.'));

        $this->resetForm();
    }

    public function archive(int $clientId): void
    {
        $result = app(ArchiveClient::class)->execute(new ArchiveClientInput(clientId: $clientId));

        if (! $result->success) {
            $this->surfaceErrors($result->errors);

            return;
        }

        unset($this->clients);

        $this->success(__('Client archived.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingClientId', 'name', 'email', 'phone', 'address', 'notes');
        $this->resetErrorBag();
    }

    private function nullable(?string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    /**
     * Surface action-layer failures: map field errors back onto the form and
     * report anything else (authorization, idempotency) as a toast.
     *
     * @param  array<int|string, mixed>  $errors
     */
    private function surfaceErrors(array $errors): void
    {
        $formFields = ['name', 'email', 'phone', 'address', 'notes'];

        foreach ($errors as $field => $message) {
            if (in_array($field, $formFields, true)) {
                $this->addError($field, (string) $message);
            } else {
                $this->error((string) $message);
            }
        }
    }
}; ?>

<div class="w-full">
        <x-header :title="__('Clients')" :subtitle="__('Manage the people and organisations you work with')" separator>
            <x-slot:actions>
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search clients') }}"
                    icon="o-magnifying-glass"
                    clearable
                    data-test="clients-search"
                />
                <x-button
                    label="{{ __('New client') }}"
                    icon="o-plus"
                    wire:click="create"
                    class="btn-primary"
                    data-test="new-client-button"
                />
            </x-slot:actions>
        </x-header>

        <x-checkbox
            wire:model.live="showArchived"
            label="{{ __('Show archived') }}"
            class="mb-4"
            data-test="toggle-archived"
        />

        <x-card>
            <x-table :headers="$this->headers" :rows="$this->clients" :link="$this->rowLink" show-empty-text :empty-text="__('No clients yet.')">
                @scope('cell_contacts_count', $client)
                    <x-badge :value="$client->contacts_count" class="badge-neutral badge-soft" />
                @endscope

                @scope('cell_status', $client)
                    <x-badge
                        :value="$client->status->label()"
                        class="{{ $client->status === \App\Enums\ClientStatus::Active ? 'badge-success' : 'badge-ghost' }} badge-soft"
                    />
                @endscope

                @scope('actions', $client)
                    <div class="flex justify-end gap-1" @click.stop>
                        <x-button
                            icon="o-pencil-square"
                            wire:click="edit({{ $client->id }})"
                            class="btn-ghost btn-sm"
                            data-test="edit-client-{{ $client->id }}"
                        />
                        @if ($client->status === \App\Enums\ClientStatus::Active)
                            <x-button
                                icon="o-archive-box"
                                wire:click="archive({{ $client->id }})"
                                wire:confirm="{{ __('Archive this client?') }}"
                                class="btn-ghost btn-sm text-error"
                                data-test="archive-client-{{ $client->id }}"
                            />
                        @endif
                    </div>
                @endscope
            </x-table>
        </x-card>

        <x-modal
            wire:model="showFormModal"
            :title="$editingClientId === null ? __('New client') : __('Edit client')"
            separator
        >
            <form wire:submit="save" class="grid gap-4" data-test="client-form">
                <x-input wire:model="name" label="{{ __('Name') }}" required data-test="client-name" />
                <x-input wire:model="email" label="{{ __('Email') }}" type="email" data-test="client-email" />
                <x-input wire:model="phone" label="{{ __('Phone') }}" data-test="client-phone" />
                <x-textarea wire:model="address" label="{{ __('Address') }}" rows="2" data-test="client-address" />
                <x-textarea wire:model="notes" label="{{ __('Notes') }}" rows="3" data-test="client-notes" />
            </form>

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showFormModal', false)" />
                <x-button
                    label="{{ __('Save') }}"
                    wire:click="save"
                    class="btn-primary"
                    data-test="save-client-button"
                />
            </x-slot:actions>
        </x-modal>
    </div>

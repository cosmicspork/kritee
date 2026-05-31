<?php

use App\Actions\Contact\CreateContact;
use App\Actions\Contact\CreateContactInput;
use App\Actions\Contact\DeleteContact;
use App\Actions\Contact\DeleteContactInput;
use App\Actions\Contact\UpdateContact;
use App\Actions\Contact\UpdateContactInput;
use App\Models\Client;
use App\Models\Contact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Client')] class extends Component {
    use Toast;

    #[Locked]
    public int $clientId;

    public bool $showFormModal = false;

    public ?int $editingContactId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $title = '';
    public bool $isPrimary = false;
    public string $notes = '';

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);

        $this->clientId = $client->id;
    }

    #[Computed]
    public function client(): Client
    {
        return Client::findOrFail($this->clientId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()
            ->where('client_id', $this->clientId)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $contactId): void
    {
        $contact = $this->findContact($contactId);

        $this->editingContactId = $contact->id;
        $this->name = $contact->name;
        $this->email = (string) $contact->email;
        $this->phone = (string) $contact->phone;
        $this->title = (string) $contact->title;
        $this->isPrimary = (bool) $contact->is_primary;
        $this->notes = (string) $contact->notes;

        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'isPrimary' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->editingContactId === null
            ? app(CreateContact::class)->execute(new CreateContactInput(
                clientId: $this->clientId,
                name: $validated['name'],
                email: $this->nullable($validated['email']),
                phone: $this->nullable($validated['phone']),
                title: $this->nullable($validated['title']),
                isPrimary: $validated['isPrimary'],
                notes: $this->nullable($validated['notes']),
            ))
            : app(UpdateContact::class)->execute(new UpdateContactInput(
                contactId: $this->editingContactId,
                name: $validated['name'],
                email: $this->nullable($validated['email']),
                phone: $this->nullable($validated['phone']),
                title: $this->nullable($validated['title']),
                isPrimary: $validated['isPrimary'],
                notes: $this->nullable($validated['notes']),
            ));

        if (! $result->success) {
            $this->surfaceErrors($result->errors);

            return;
        }

        unset($this->contacts);
        $this->showFormModal = false;

        $this->success($this->editingContactId === null
            ? __('Contact added.')
            : __('Contact updated.'));

        $this->resetForm();
    }

    public function delete(int $contactId): void
    {
        $contact = $this->findContact($contactId);

        $result = app(DeleteContact::class)->execute(new DeleteContactInput(contactId: $contact->id));

        if (! $result->success) {
            $this->surfaceErrors($result->errors);

            return;
        }

        unset($this->contacts);

        $this->success(__('Contact deleted.'));
    }

    /**
     * Resolve a contact, guarding that it belongs to the client this page renders.
     */
    private function findContact(int $contactId): Contact
    {
        return Contact::query()
            ->where('client_id', $this->clientId)
            ->findOrFail($contactId);
    }

    private function resetForm(): void
    {
        $this->reset('editingContactId', 'name', 'email', 'phone', 'title', 'isPrimary', 'notes');
        $this->resetErrorBag();
    }

    private function nullable(?string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function surfaceErrors(array $errors): void
    {
        $formFields = ['name', 'email', 'phone', 'title', 'notes'];

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
        <x-header :title="$this->client->name" :subtitle="$this->client->email" separator>
            <x-slot:actions>
                @if (\Illuminate\Support\Facades\Route::has('clients.index'))
                    <x-button
                        label="{{ __('Back to clients') }}"
                        icon="o-arrow-left"
                        link="{{ route('clients.index') }}"
                        class="btn-ghost"
                        data-test="back-to-clients"
                    />
                @endif
                <x-button
                    label="{{ __('Add contact') }}"
                    icon="o-plus"
                    wire:click="create"
                    class="btn-primary"
                    data-test="new-contact-button"
                />
            </x-slot:actions>
        </x-header>

        <x-card :title="__('Contacts')">
            @if ($this->contacts->isEmpty())
                <div class="py-6 text-center text-base-content/50">{{ __('No contacts yet.') }}</div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->contacts as $contact)
                            <tr wire:key="contact-{{ $contact->id }}">
                                <td>
                                    {{ $contact->name }}
                                    @if ($contact->is_primary)
                                        <x-badge :value="__('Primary')" class="badge-primary badge-soft ms-1" />
                                    @endif
                                </td>
                                <td class="text-base-content/70">{{ $contact->title }}</td>
                                <td class="text-base-content/70">{{ $contact->email }}</td>
                                <td class="text-base-content/70">{{ $contact->phone }}</td>
                                <td class="text-end">
                                    <div class="flex justify-end gap-1">
                                        <x-button
                                            icon="o-pencil-square"
                                            wire:click="edit({{ $contact->id }})"
                                            class="btn-ghost btn-sm"
                                            data-test="edit-contact-{{ $contact->id }}"
                                        />
                                        <x-button
                                            icon="o-trash"
                                            wire:click="delete({{ $contact->id }})"
                                            wire:confirm="{{ __('Delete this contact?') }}"
                                            class="btn-ghost btn-sm text-error"
                                            data-test="delete-contact-{{ $contact->id }}"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>

        <x-modal
            wire:model="showFormModal"
            :title="$editingContactId === null ? __('Add contact') : __('Edit contact')"
            separator
        >
            <form wire:submit="save" class="grid gap-4" data-test="contact-form">
                <x-input wire:model="name" label="{{ __('Name') }}" required data-test="contact-name" />
                <x-input wire:model="title" label="{{ __('Title') }}" data-test="contact-title" />
                <x-input wire:model="email" label="{{ __('Email') }}" type="email" data-test="contact-email" />
                <x-input wire:model="phone" label="{{ __('Phone') }}" data-test="contact-phone" />
                <x-checkbox wire:model="isPrimary" label="{{ __('Primary contact') }}" data-test="contact-primary" />
                <x-textarea wire:model="notes" label="{{ __('Notes') }}" rows="3" data-test="contact-notes" />
            </form>

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showFormModal', false)" />
                <x-button
                    label="{{ __('Save') }}"
                    wire:click="save"
                    class="btn-primary"
                    data-test="save-contact-button"
                />
            </x-slot:actions>
        </x-modal>
    </div>

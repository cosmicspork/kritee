<?php

use App\Actions\Invoice\DraftInvoice;
use App\Actions\Invoice\DraftInvoiceInput;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('New invoice')] class extends Component {
    use Toast;

    public ?int $clientId = null;
    public string $notes = '';
    public string $terms = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('create', Invoice::class), 403);
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return $this->clients
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])
            ->all();
    }

    public function draft(DraftInvoice $action): void
    {
        $this->validate([
            'clientId' => ['required', 'integer'],
        ]);

        $result = $action->execute(new DraftInvoiceInput(
            clientId: (int) $this->clientId,
            notes: $this->notes !== '' ? $this->notes : null,
            terms: $this->terms !== '' ? $this->terms : null,
        ));

        if (! $result->success) {
            $this->error(__('Could not draft the invoice.'), description: implode(' ', $result->errors));

            return;
        }

        $this->success(__('Draft invoice :number created.', ['number' => $result->data->invoice_number]));

        $this->redirect(route('invoices.show', $result->data), navigate: true);
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('New invoice') }}</h1>
            <p class="text-sm text-base-content/60">{{ __('Open a draft for a client, then pull their unbilled work onto it.') }}</p>
        </div>

        <x-card>
            <form wire:submit="draft" class="space-y-6">
                <x-select
                    wire:model="clientId"
                    label="{{ __('Client') }}"
                    placeholder="{{ __('Select a client') }}"
                    :options="$this->clientOptions"
                    required
                    data-test="draft-client"
                />

                <x-textarea
                    wire:model="notes"
                    label="{{ __('Notes') }}"
                    placeholder="{{ __('Optional notes shown on the invoice') }}"
                    rows="3"
                    data-test="draft-notes"
                />

                <x-textarea
                    wire:model="terms"
                    label="{{ __('Terms') }}"
                    placeholder="{{ __('Optional payment terms') }}"
                    rows="3"
                    data-test="draft-terms"
                />

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('invoices.index') }}" wire:navigate>
                        <x-button label="{{ __('Cancel') }}" class="btn-ghost" />
                    </a>
                    <x-button type="submit" label="{{ __('Create draft') }}" class="btn-primary" data-test="create-draft-button" />
                </div>
            </form>
        </x-card>
    </div>

<?php

use App\Actions\Expense\RecordExpense;
use App\Actions\Expense\RecordExpenseInput;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('Record expense')] class extends Component {
    use Toast, WithFileUploads;

    #[Validate('required|numeric|min:0')]
    public string $amount = '';

    #[Validate('required|date')]
    public string $incurredOn = '';

    public string $description = '';

    public ?int $clientId = null;

    public ?int $projectId = null;

    public ?string $category = null;

    public bool $isBillable = true;

    public ?string $notes = null;

    #[Validate('nullable|file|mimes:pdf,png,jpg,jpeg,webp|max:5120')]
    public $receipt = null;

    public function mount(): void
    {
        $this->incurredOn = now()->toDateString();
    }

    /**
     * @return array<int, array{id: int|string, name: string}>
     */
    public function clientOptions(): array
    {
        $options = Client::orderBy('name')->get(['id', 'name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name])
            ->all();

        return array_merge([['id' => '', 'name' => __('— None —')]], $options);
    }

    /**
     * @return array<int, array{id: int|string, name: string}>
     */
    public function projectOptions(): array
    {
        $query = Project::orderBy('name');

        if ($this->clientId !== null) {
            $query->where('client_id', $this->clientId);
        }

        $options = $query->get(['id', 'name'])
            ->map(fn (Project $project): array => ['id' => $project->id, 'name' => $project->name])
            ->all();

        return array_merge([['id' => '', 'name' => __('— None —')]], $options);
    }

    public function updatedClientId(): void
    {
        $this->projectId = null;
    }

    /**
     * Persist a new expense through the action layer.
     */
    public function save(RecordExpense $action): void
    {
        $this->validate();

        $payload = [
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'incurred_on' => $this->incurredOn,
            'client_id' => $this->clientId ?: null,
            'project_id' => $this->projectId ?: null,
            'category' => $this->category ?: null,
            'is_billable' => $this->isBillable,
            'notes' => $this->notes ?: null,
            'receipt' => $this->receiptPayload(),
        ];

        // The description column is non-nullable; leaving the key out lets the
        // input fall back to its empty-string default rather than tripping the
        // required rule a blank value would.
        if ($this->description !== '') {
            $payload['description'] = $this->description;
        }

        $result = $action->execute(RecordExpenseInput::validateAndCreate($payload));

        if (! $result->success) {
            $this->mapErrors($result->errors);

            return;
        }

        $this->success(__('Expense recorded.'), redirectTo: route('expenses.index'));
    }

    /**
     * Stores the upload on the public disk and returns the metadata the action persists,
     * or null when no receipt was attached.
     *
     * @return array{filename: string, path: string, mime_type: string, size_bytes: int}|null
     */
    private function receiptPayload(): ?array
    {
        if ($this->receipt === null) {
            return null;
        }

        $path = $this->receipt->store('receipts', 'public');

        return [
            'filename' => $this->receipt->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->receipt->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $this->receipt->getSize() ?: 0,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function mapErrors(array $errors): void
    {
        foreach ($errors as $field => $message) {
            $this->addError(is_string($field) ? $field : 'expense', is_string($message) ? $message : __('Invalid value.'));
        }

        $first = collect($errors)->flatten()->first();
        $this->error(is_string($first) ? $first : __('Could not record the expense.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ __('Record expense') }}</h1>
        <x-button :label="__('Back')" icon="o-arrow-left" :link="route('expenses.index')" class="btn-ghost" />
    </div>

    <x-card>
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <x-input
                wire:model="amount"
                :label="__('Amount')"
                type="number"
                step="0.01"
                min="0"
                prefix="$"
                required
                data-test="expense-amount"
            />

            <x-input
                wire:model="incurredOn"
                :label="__('Date incurred')"
                type="date"
                required
                data-test="expense-incurred-on"
            />

            <div class="sm:col-span-2">
                <x-input
                    wire:model="description"
                    :label="__('Description')"
                    :placeholder="__('What was this for?')"
                    data-test="expense-description"
                />
            </div>

            <x-select
                wire:model.live="clientId"
                :label="__('Client')"
                :options="$this->clientOptions()"
                data-test="expense-client"
            />

            <x-select
                wire:model="projectId"
                :label="__('Project')"
                :options="$this->projectOptions()"
                data-test="expense-project"
            />

            <x-input
                wire:model="category"
                :label="__('Category')"
                :placeholder="__('e.g. travel, software')"
                data-test="expense-category"
            />

            <div class="flex items-end">
                <x-checkbox wire:model="isBillable" :label="__('Billable to client')" data-test="expense-billable" />
            </div>

            <div class="sm:col-span-2">
                <x-textarea wire:model="notes" :label="__('Notes')" rows="3" data-test="expense-notes" />
            </div>

            <div class="sm:col-span-2">
                <x-file
                    wire:model="receipt"
                    :label="__('Receipt')"
                    :hint="__('PDF or image, up to 5MB')"
                    accept="application/pdf,image/*"
                    data-test="expense-receipt"
                />
            </div>

            <div class="sm:col-span-2 flex justify-end gap-2">
                <x-button :label="__('Cancel')" :link="route('expenses.index')" class="btn-ghost" />
                <x-button type="submit" :label="__('Record expense')" class="btn-primary" spinner="save" data-test="save-expense-button" />
            </div>
        </form>
    </x-card>
</div>

<?php

use App\Actions\Expense\UpdateExpense;
use App\Actions\Expense\UpdateExpenseInput;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('Edit expense')] class extends Component {
    use Toast;

    #[Locked]
    public int $expenseId;

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

    /**
     * Hydrate the form from the expense, authorizing before exposing any data.
     */
    public function mount(Expense $expense): void
    {
        $this->authorize('update', $expense);

        $this->expenseId = $expense->getKey();
        $this->amount = (string) $expense->amount;
        $this->incurredOn = $expense->incurred_on->toDateString();
        $this->description = (string) $expense->description;
        $this->clientId = $expense->client_id;
        $this->projectId = $expense->project_id;
        $this->category = $expense->category;
        $this->isBillable = (bool) $expense->is_billable;
        $this->notes = $expense->notes;
    }

    /**
     * @return Expense
     */
    public function expense(): Expense
    {
        return Expense::with('attachments')->findOrFail($this->expenseId);
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
     * Persist edits through the action layer.
     */
    public function save(UpdateExpense $action): void
    {
        $this->validate();

        $result = $action->execute(UpdateExpenseInput::validateAndCreate([
            'expense_id' => $this->expenseId,
            'amount' => $this->amount,
            'incurred_on' => $this->incurredOn,
            'description' => $this->description,
            'client_id' => $this->clientId ?: null,
            'project_id' => $this->projectId ?: null,
            'category' => $this->category ?: null,
            'is_billable' => $this->isBillable,
            'notes' => $this->notes ?: null,
        ]));

        if (! $result->success) {
            $this->mapErrors($result->errors);

            return;
        }

        $this->success(__('Expense updated.'), redirectTo: route('expenses.index'));
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
        $this->error(is_string($first) ? $first : __('Could not update the expense.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ __('Edit expense') }}</h1>
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

            @if ($this->expense()->attachments->isNotEmpty())
                <div class="sm:col-span-2">
                    <h3 class="mb-2 text-sm font-medium text-base-content/70">{{ __('Receipts') }}</h3>
                    <ul class="space-y-1">
                        @foreach ($this->expense()->attachments as $attachment)
                            <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-2 text-sm">
                                <x-icon name="o-paper-clip" class="h-4 w-4 text-base-content/50" />
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}" target="_blank" class="link link-hover">
                                    {{ $attachment->filename }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="sm:col-span-2 flex justify-end gap-2">
                <x-button :label="__('Cancel')" :link="route('expenses.index')" class="btn-ghost" />
                <x-button type="submit" :label="__('Save changes')" class="btn-primary" spinner="save" data-test="save-expense-button" />
            </div>
        </form>
    </x-card>
</div>

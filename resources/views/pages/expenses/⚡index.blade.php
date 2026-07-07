<?php

use App\Actions\Expense\DeleteExpense;
use App\Actions\Expense\DeleteExpenseInput;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('Expenses')] class extends Component {
    use Toast, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $billable = '';

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Expense>
     */
    #[Computed]
    public function expenses()
    {
        return Expense::query()
            ->with(['client', 'project'])
            ->when($this->onlyOwn(), fn ($query) => $query->where('user_id', Auth::id()))
            ->when($this->search !== '', fn ($query) => $query->where('description', 'like', "%{$this->search}%"))
            ->when($this->billable === 'billable', fn ($query) => $query->where('is_billable', true))
            ->when($this->billable === 'non-billable', fn ($query) => $query->where('is_billable', false))
            ->latest('incurred_on')
            ->paginate(15);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function billableOptions(): array
    {
        return [
            ['id' => '', 'name' => __('All')],
            ['id' => 'billable', 'name' => __('Billable')],
            ['id' => 'non-billable', 'name' => __('Non-billable')],
        ];
    }

    /**
     * Members see only their own expenses; admins see everyone's.
     */
    private function onlyOwn(): bool
    {
        return ! Auth::user()->isAdmin();
    }

    /**
     * Remove an expense through the action layer, surfacing any failure as a toast.
     */
    public function delete(DeleteExpense $action, int $expense): void
    {
        $result = $action->execute(DeleteExpenseInput::validateAndCreate([
            'expense_id' => $expense,
        ]));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        unset($this->expenses);

        $this->success(__('Expense deleted.'));
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function firstError(array $errors): string
    {
        $first = collect($errors)->flatten()->first();

        return is_string($first) ? $first : __('Something went wrong.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBillable(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Expenses') }}</h1>
            <p class="text-sm text-base-content/70">{{ __('Track and bill costs incurred on client work.') }}</p>
        </div>

        <x-button
            :label="__('Record expense')"
            icon="o-plus"
            :link="route('expenses.create')"
            class="btn-primary"
            data-test="record-expense-button"
        />
    </div>

    <div class="grid w-full gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
        <x-input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            icon="o-magnifying-glass"
            :placeholder="__('Filter by description')"
            clearable
            data-test="expense-search"
        />

        <x-select
            wire:model.live="billable"
            :label="__('Billable')"
            :options="$this->billableOptions"
            data-test="expense-billable-filter"
        />
    </div>

    @if ($this->expenses->isEmpty())
        <x-card class="text-center">
            <x-icon name="o-receipt-percent" class="mx-auto mb-2 h-10 w-10 text-base-content/40" />
            <p class="text-base-content/70">{{ __('No expenses recorded yet.') }}</p>
        </x-card>
    @else
        <div class="overflow-x-auto">
        <table class="table" data-test="expenses-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th>{{ __('Billable') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->expenses as $expense)
                    <tr wire:key="expense-{{ $expense->id }}">
                        <td class="whitespace-nowrap">{{ $expense->incurred_on->format('M j, Y') }}</td>
                        <td>{{ $expense->description ?: '—' }}</td>
                        <td class="text-base-content/70">{{ $expense->client?->name ?? '—' }}</td>
                        <td class="text-base-content/70">{{ $expense->category ?? '—' }}</td>
                        <td class="text-end font-mono">{{ \App\Services\Support\MoneyFormatter::format($expense->amount) }}</td>
                        <td>
                            @if ($expense->is_billable)
                                <x-badge :value="__('Billable')" class="badge-soft badge-success" />
                            @else
                                <x-badge :value="__('Non-billable')" class="badge-soft" />
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="flex justify-end gap-1">
                                <x-button
                                    icon="o-pencil-square"
                                    :link="route('expenses.edit', $expense)"
                                    class="btn-ghost btn-sm"
                                    :title="__('Edit')"
                                    data-test="edit-expense-{{ $expense->id }}"
                                />
                                <x-button
                                    icon="o-trash"
                                    wire:click="delete({{ $expense->id }})"
                                    wire:confirm="{{ __('Delete this expense?') }}"
                                    class="btn-ghost btn-sm text-error"
                                    :title="__('Delete')"
                                    data-test="delete-expense-{{ $expense->id }}"
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div>{{ $this->expenses->links() }}</div>
    @endif
</div>

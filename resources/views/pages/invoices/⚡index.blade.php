<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app.sidebar'), Title('Invoices')] class extends Component {
    #[Url]
    public string $status = '';

    /**
     * @return Collection<int, Invoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        return Invoice::query()
            ->with('client')
            ->when($this->statusFilter(), fn ($query, InvoiceStatus $status) => $query->where('status', $status))
            ->latest()
            ->get();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            ['id' => '', 'name' => __('All statuses')],
            ...array_map(
                fn (InvoiceStatus $status): array => ['id' => $status->value, 'name' => $status->label()],
                InvoiceStatus::cases(),
            ),
        ];
    }

    private function statusFilter(): ?InvoiceStatus
    {
        return InvoiceStatus::tryFrom($this->status);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('Invoices') }}</h1>
                <p class="text-sm text-base-content/60">{{ __('Draft, send, and reconcile client invoices.') }}</p>
            </div>

            <div class="flex items-end gap-3">
                <x-select
                    wire:model.live="status"
                    label="{{ __('Status') }}"
                    :options="$this->statusOptions"
                    data-test="invoice-status-filter"
                />

                <a href="{{ route('invoices.create') }}" wire:navigate data-test="new-invoice-link">
                    <x-button label="{{ __('New invoice') }}" icon="o-plus" class="btn-primary" />
                </a>
            </div>
        </div>

        @if ($this->invoices->isEmpty())
            <div class="rounded-xl border border-dashed border-base-content/20 p-12 text-center text-base-content/60" data-test="invoices-empty">
                {{ __('No invoices yet. Draft your first one to get started.') }}
            </div>
        @else
            <div class="overflow-x-auto">
            <table class="table" data-test="invoices-table">
                <thead>
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Client') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Issued') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->id }}" class="hover">
                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" wire:navigate class="link link-hover font-medium" data-test="invoice-link-{{ $invoice->id }}">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="text-base-content/70">{{ $invoice->client?->name ?? __('—') }}</td>
                            <td>
                                <x-badge :value="$invoice->status->label()" class="badge-soft @class([
                                    'badge-warning' => $invoice->status === \App\Enums\InvoiceStatus::Overdue,
                                    'badge-success' => $invoice->status === \App\Enums\InvoiceStatus::Paid,
                                    'badge-ghost' => $invoice->status === \App\Enums\InvoiceStatus::Void,
                                ])" />
                            </td>
                            <td class="text-base-content/70">{{ $invoice->issued_at?->toFormattedDateString() ?? __('—') }}</td>
                            <td class="text-end font-mono">{{ \App\Services\Support\MoneyFormatter::format($invoice->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

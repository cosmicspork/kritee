<?php

use App\Actions\Contracts\ActionResult;
use App\Actions\Invoice\AddLineItemsFromUnbilled;
use App\Actions\Invoice\AddLineItemsFromUnbilledInput;
use App\Actions\Invoice\MarkInvoicePaid;
use App\Actions\Invoice\MarkInvoicePaidInput;
use App\Actions\Invoice\SendInvoice;
use App\Actions\Invoice\SendInvoiceInput;
use App\Actions\Invoice\VoidInvoice;
use App\Actions\Invoice\VoidInvoiceInput;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\LineItem;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts::app.sidebar'), Title('Invoice')] class extends Component {
    use Toast;

    #[Locked]
    public int $invoiceId;

    public function mount(Invoice $invoice): void
    {
        $this->invoiceId = $invoice->getKey();
    }

    #[Computed]
    public function invoice(): Invoice
    {
        return Invoice::with('client')->findOrFail($this->invoiceId);
    }

    /**
     * @return Collection<int, LineItem>
     */
    #[Computed]
    public function lineItems(): Collection
    {
        return $this->invoice->lineItems()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function pullUnbilled(AddLineItemsFromUnbilled $action): void
    {
        $result = $action->execute(new AddLineItemsFromUnbilledInput(
            invoiceId: $this->invoiceId,
        ));

        $this->refreshAfter($result, __('Unbilled work pulled onto the invoice.'));
    }

    public function send(SendInvoice $action): void
    {
        $result = $action->execute(new SendInvoiceInput(
            invoiceId: $this->invoiceId,
        ));

        $this->refreshAfter($result, __('Invoice sent.'));
    }

    public function markPaid(MarkInvoicePaid $action): void
    {
        $result = $action->execute(new MarkInvoicePaidInput(
            invoiceId: $this->invoiceId,
        ));

        $this->refreshAfter($result, __('Invoice marked paid.'));
    }

    public function void(VoidInvoice $action): void
    {
        $result = $action->execute(new VoidInvoiceInput(
            invoiceId: $this->invoiceId,
        ));

        $this->refreshAfter($result, __('Invoice voided.'));
    }

    private function refreshAfter(ActionResult $result, string $message): void
    {
        if (! $result->success) {
            $this->error(__('That action could not be completed.'), description: implode(' ', $result->errors));

            return;
        }

        unset($this->invoice, $this->lineItems);

        $this->success($message);
    }
}; ?>

@php($invoice = $this->invoice)
@php($status = $invoice->status)

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <a href="{{ route('invoices.index') }}" wire:navigate class="link link-hover text-sm text-base-content/60">
                    {{ __('← Invoices') }}
                </a>
                <h1 class="text-2xl font-semibold">{{ $invoice->invoice_number }}</h1>
                <div class="flex items-center gap-2 text-sm text-base-content/70">
                    <span>{{ $invoice->client?->name ?? __('No client') }}</span>
                    <x-badge :value="$status->label()" class="badge-soft" data-test="invoice-status" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($status === \App\Enums\InvoiceStatus::Draft)
                    <x-button
                        label="{{ __('Pull unbilled work') }}"
                        icon="o-arrow-down-tray"
                        wire:click="pullUnbilled"
                        wire:loading.attr="disabled"
                        class="btn-outline"
                        data-test="pull-unbilled-button"
                    />
                    <x-button
                        label="{{ __('Send') }}"
                        icon="o-paper-airplane"
                        wire:click="send"
                        wire:confirm="{{ __('Send this invoice to the client?') }}"
                        class="btn-primary"
                        data-test="send-invoice-button"
                    />
                @endif

                @if (in_array($status, [\App\Enums\InvoiceStatus::Sent, \App\Enums\InvoiceStatus::Viewed, \App\Enums\InvoiceStatus::Overdue], true))
                    <x-button
                        label="{{ __('Mark paid') }}"
                        icon="o-check-circle"
                        wire:click="markPaid"
                        class="btn-success"
                        data-test="mark-paid-button"
                    />
                @endif

                @if ($status !== \App\Enums\InvoiceStatus::Paid && $status !== \App\Enums\InvoiceStatus::Void)
                    <x-button
                        label="{{ __('Void') }}"
                        icon="o-x-circle"
                        wire:click="void"
                        wire:confirm="{{ __('Void this invoice? This cannot be undone.') }}"
                        class="btn-ghost text-error"
                        data-test="void-invoice-button"
                    />
                @endif
            </div>
        </div>

        <x-card>
            <table class="table" data-test="line-items-table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Qty') }}</th>
                        <th class="text-end">{{ __('Unit price') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->lineItems as $item)
                        <tr wire:key="line-{{ $item->id }}">
                            <td>{{ $item->description }}</td>
                            <td class="text-end font-mono">{{ $item->quantity }}</td>
                            <td class="text-end font-mono">{{ $item->unit_price }}</td>
                            <td class="text-end font-mono">{{ $item->amount }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-base-content/60" data-test="no-line-items">
                                {{ __('No line items yet.') }}
                                @if ($status === \App\Enums\InvoiceStatus::Draft)
                                    {{ __('Pull this client\'s unbilled work to populate the invoice.') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end font-medium">{{ __('Subtotal') }}</td>
                        <td class="text-end font-mono" data-test="invoice-subtotal">{{ $invoice->subtotal }}</td>
                    </tr>
                    @if ($invoice->tax_amount !== null)
                        <tr>
                            <td colspan="3" class="text-end text-base-content/70">{{ __('Tax') }}</td>
                            <td class="text-end font-mono">{{ $invoice->tax_amount }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="text-end text-lg font-semibold">{{ __('Total') }}</td>
                        <td class="text-end font-mono text-lg font-semibold" data-test="invoice-total">{{ $invoice->total }}</td>
                    </tr>
                </tfoot>
            </table>
        </x-card>

        @if ($invoice->notes || $invoice->terms)
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($invoice->notes)
                    <x-card title="{{ __('Notes') }}">
                        <p class="text-sm whitespace-pre-line text-base-content/70">{{ $invoice->notes }}</p>
                    </x-card>
                @endif
                @if ($invoice->terms)
                    <x-card title="{{ __('Terms') }}">
                        <p class="text-sm whitespace-pre-line text-base-content/70">{{ $invoice->terms }}</p>
                    </x-card>
                @endif
            </div>
        @endif
    </div>

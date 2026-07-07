<?php

use App\Actions\TimeEntry\StopTimer;
use App\Actions\TimeEntry\StopTimerInput;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Services\Billing\BillingRateCascade;
use App\Services\Support\DurationFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Dashboard')] class extends Component {
    use Toast;

    /**
     * The user's open timer, if one is currently running.
     */
    #[Computed]
    public function runningEntry(): ?TimeEntry
    {
        return TimeEntry::query()
            ->with(['client', 'project'])
            ->where('user_id', Auth::id())
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    /**
     * Billable, unbilled work valued through the rate cascade. Entries that
     * resolve no rate are counted at zero rather than hidden: the hours are
     * real even when the price is not configured yet.
     */
    #[Computed]
    public function unbilledTotal(): float
    {
        $cascade = app(BillingRateCascade::class);

        return TimeEntry::query()
            ->with(['ticket', 'project', 'client', 'user'])
            ->where('is_billable', true)
            ->where('is_billed', false)
            ->where(fn ($query) => $query
                ->whereNotNull('ended_at')
                ->orWhereNull('started_at'))
            ->get()
            ->sum(function (TimeEntry $entry) use ($cascade): float {
                $rate = $cascade->resolve($entry);

                return $rate === null ? 0.0 : ($entry->duration_minutes / 60) * (float) $rate;
            });
    }

    /**
     * Invoices past their due date and still awaiting payment.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Invoice>
     */
    #[Computed]
    public function overdueInvoices()
    {
        return Invoice::query()
            ->with('client')
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Viewed, InvoiceStatus::Overdue])
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', CarbonImmutable::now()->toDateString())
            ->orderBy('due_at')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Ticket>
     */
    #[Computed]
    public function blockedTickets()
    {
        return Ticket::query()
            ->with('client')
            ->where('is_blocked', true)
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    /**
     * Minutes the user logged per day of the current week, Monday first.
     *
     * @return array<int, array{label: string, minutes: int, isToday: bool}>
     */
    #[Computed]
    public function weekDays(): array
    {
        $start = CarbonImmutable::now()->startOfWeek();

        $sums = TimeEntry::query()
            ->where('user_id', Auth::id())
            ->where(fn ($query) => $query
                ->whereNotNull('ended_at')
                ->orWhereNull('started_at'))
            ->whereBetween('created_at', [$start, $start->endOfWeek()])
            ->get(['duration_minutes', 'created_at'])
            ->groupBy(fn (TimeEntry $entry): string => $entry->created_at->toDateString())
            ->map(fn ($entries): int => (int) $entries->sum('duration_minutes'));

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);

            $days[] = [
                'label' => $day->isoFormat('ddd'),
                'minutes' => $sums->get($day->toDateString(), 0),
                'isToday' => $day->isToday(),
            ];
        }

        return $days;
    }

    #[Computed]
    public function weekTotalMinutes(): int
    {
        return array_sum(array_column($this->weekDays, 'minutes'));
    }

    public function stopTimer(StopTimer $stopTimer): void
    {
        $running = $this->runningEntry;

        if ($running === null) {
            $this->error(__('No timer is running.'));

            return;
        }

        $result = $stopTimer->execute(new StopTimerInput(timeEntryId: $running->getKey()));

        if (! $result->success) {
            $first = reset($result->errors);
            $this->error(is_string($first) ? $first : __('Could not stop the timer.'));

            return;
        }

        unset($this->runningEntry, $this->unbilledTotal, $this->weekDays, $this->weekTotalMinutes);

        $this->success(__('Timer stopped.'));
    }

    public function formatMinutes(int $minutes): string
    {
        return DurationFormatter::minutes($minutes);
    }

    public function formatMoney(float $amount): string
    {
        return (string) Number::currency($amount);
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-8 flex items-baseline justify-between">
        <h1 class="font-serif text-2xl font-semibold">{{ now()->isoFormat('dddd') }}</h1>
        <span class="font-mono text-sm text-base-content/60">{{ now()->isoFormat('D MMMM YYYY') }}</span>
    </div>

    {{-- The ledger page: a madder margin rule with section labels hanging in
         the margin, like the ruled account book the app is named for. --}}
    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-[5.5rem_1fr]">
        <div class="pt-1 text-xs font-semibold uppercase tracking-wider text-base-content/60 sm:border-e-2 sm:border-(--madder) sm:pe-3 sm:text-end">
            {{ __('Now') }}
        </div>
        <div class="sm:ps-5">
            @if ($this->runningEntry)
                <div
                    class="flex flex-wrap items-center gap-x-6 gap-y-3 rounded-box border border-base-300 bg-base-100 p-5 shadow-sm"
                    data-test="running-timer"
                    x-data="{
                        start: {{ $this->runningEntry->started_at->getTimestamp() }},
                        now: Math.floor(Date.now() / 1000),
                        display() {
                            const s = Math.max(this.now - this.start, 0);
                            const h = Math.floor(s / 3600);
                            const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
                            const ss = String(s % 60).padStart(2, '0');
                            return `${h}:${m}:${ss}`;
                        },
                        init() { setInterval(() => this.now = Math.floor(Date.now() / 1000), 1000); },
                    }"
                >
                    <span class="font-serif text-4xl font-semibold tabular-nums" x-text="display()"></span>
                    <div class="min-w-48 flex-1">
                        <p class="font-medium">
                            <span class="me-1 inline-block h-2 w-2 rounded-full bg-success" aria-hidden="true"></span>
                            {{ $this->runningEntry->description ?: __('Untitled timer') }}
                        </p>
                        <p class="mt-0.5 text-sm text-base-content/60">
                            {{ collect([
                                $this->runningEntry->client?->name,
                                __('started :time', ['time' => $this->runningEntry->started_at->format('H:i')]),
                                $this->runningEntry->is_billable ? __('billable') : __('non-billable'),
                            ])->filter()->implode(' · ') }}
                        </p>
                    </div>
                    <x-button
                        :label="__('Stop timer')"
                        icon="o-stop-circle"
                        wire:click="stopTimer"
                        spinner="stopTimer"
                        class="btn-primary"
                        data-test="stop-timer-button"
                    />
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-box border border-base-300 bg-base-100 p-5" data-test="no-running-timer">
                    <p class="text-base-content/60">{{ __('No timer running.') }}</p>
                    <x-button
                        :label="__('Start a timer')"
                        icon="o-play-circle"
                        :link="route('time.index')"
                        class="btn-primary btn-outline"
                    />
                </div>
            @endif
        </div>

        <div class="pt-1 text-xs font-semibold uppercase tracking-wider text-base-content/60 sm:border-e-2 sm:border-(--madder) sm:pe-3 sm:text-end">
            {{ __('Standing') }}
        </div>
        <div class="sm:ps-5">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-box border border-base-300 bg-base-100 p-4">
                    <div class="font-serif text-xl font-semibold tabular-nums" data-test="unbilled-total">{{ $this->formatMoney($this->unbilledTotal) }}</div>
                    <div class="mt-0.5 text-xs text-base-content/60">{{ __('Unbilled work') }}</div>
                </div>
                <div class="rounded-box border p-4 {{ $this->overdueInvoices->isEmpty() ? 'border-base-300 bg-base-100' : 'border-error/50 bg-base-100' }}">
                    <div class="font-serif text-xl font-semibold tabular-nums {{ $this->overdueInvoices->isEmpty() ? '' : 'text-error' }}" data-test="overdue-total">
                        {{ $this->formatMoney((float) $this->overdueInvoices->sum('total')) }}
                    </div>
                    <div class="mt-0.5 text-xs text-base-content/60">
                        {{ trans_choice('Overdue · :count invoice|Overdue · :count invoices', $this->overdueInvoices->count(), ['count' => $this->overdueInvoices->count()]) }}
                    </div>
                </div>
                <div class="rounded-box border border-base-300 bg-base-100 p-4">
                    <div class="font-serif text-xl font-semibold tabular-nums" data-test="blocked-count">{{ $this->blockedTickets->count() }}</div>
                    <div class="mt-0.5 text-xs text-base-content/60">{{ __('Blocked tickets') }}</div>
                </div>
                <div class="rounded-box border border-base-300 bg-base-100 p-4">
                    <div class="font-serif text-xl font-semibold tabular-nums" data-test="week-total">{{ $this->formatMinutes($this->weekTotalMinutes) }}</div>
                    <div class="mt-0.5 text-xs text-base-content/60">{{ __('Logged this week') }}</div>
                </div>
            </div>
        </div>

        <div class="pt-1 text-xs font-semibold uppercase tracking-wider text-base-content/60 sm:border-e-2 sm:border-(--madder) sm:pe-3 sm:text-end">
            {{ __('Attention') }}
        </div>
        <div class="sm:ps-5">
            @if ($this->blockedTickets->isEmpty() && $this->overdueInvoices->isEmpty())
                <div class="rounded-box border border-base-300 bg-base-100 p-5 text-base-content/60" data-test="nothing-needs-attention">
                    {{ __('Nothing needs attention.') }}
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($this->blockedTickets as $ticket)
                        <div class="flex items-center gap-4 rounded-box border border-base-300 bg-base-100 px-4 py-3" wire:key="blocked-{{ $ticket->id }}">
                            <x-badge :value="__('Blocked')" class="badge-error badge-soft badge-sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $ticket->title }}</p>
                                <p class="text-xs text-base-content/60">
                                    {{ collect([$ticket->client?->name, $ticket->priority->label()])->filter()->implode(' · ') }}
                                </p>
                            </div>
                            <span class="font-mono text-xs text-base-content/50">{{ $ticket->key }}</span>
                            <x-button :label="__('Open')" :link="route('tickets.board')" class="btn-ghost btn-sm" />
                        </div>
                    @endforeach

                    @foreach ($this->overdueInvoices as $invoice)
                        <div class="flex items-center gap-4 rounded-box border border-base-300 bg-base-100 px-4 py-3" wire:key="overdue-{{ $invoice->id }}">
                            <x-badge :value="trans_choice(':days day overdue|:days days overdue', (int) $invoice->due_at->diffInDays(now()), ['days' => (int) $invoice->due_at->diffInDays(now())])" class="badge-error badge-soft badge-sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $invoice->invoice_number }} — {{ $this->formatMoney((float) $invoice->total) }}</p>
                                <p class="text-xs text-base-content/60">{{ $invoice->client?->name }}</p>
                            </div>
                            <x-button :label="__('Open invoice')" :link="route('invoices.show', $invoice)" class="btn-ghost btn-sm" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pt-1 text-xs font-semibold uppercase tracking-wider text-base-content/60 sm:border-e-2 sm:border-(--madder) sm:pe-3 sm:text-end">
            {{ __('This week') }}
        </div>
        <div class="sm:ps-5">
            <div class="grid grid-cols-7 gap-2" data-test="week-strip">
                @foreach ($this->weekDays as $day)
                    <div class="rounded-box border bg-base-100 px-2 py-2 text-center sm:px-3 {{ $day['isToday'] ? 'border-primary shadow-[inset_0_2px_0_0] shadow-primary' : 'border-base-300' }}">
                        <div class="text-xs text-base-content/60">{{ $day['label'] }}</div>
                        <div class="mt-0.5 font-mono text-xs tabular-nums sm:text-sm {{ $day['minutes'] === 0 ? 'text-base-content/40' : '' }}">
                            {{ $day['minutes'] === 0 ? '—' : $this->formatMinutes($day['minutes']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

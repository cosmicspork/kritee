<?php

use App\Actions\TimeEntry\DeleteTimeEntry;
use App\Actions\TimeEntry\DeleteTimeEntryInput;
use App\Actions\TimeEntry\RecordManualTimeEntry;
use App\Actions\TimeEntry\RecordManualTimeEntryInput;
use App\Actions\TimeEntry\StartTimer;
use App\Actions\TimeEntry\StartTimerInput;
use App\Actions\TimeEntry\StopTimer;
use App\Actions\TimeEntry\StopTimerInput;
use App\Actions\TimeEntry\UpdateTimeEntry;
use App\Actions\TimeEntry\UpdateTimeEntryInput;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use DateTimeInterface as NativeDateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Time')] class extends Component {
    use Toast;

    public string $timerDescription = '';
    public ?int $timerClientId = null;
    public ?int $timerProjectId = null;
    public bool $timerIsBillable = true;

    public int $manualDurationMinutes = 0;
    public string $manualStartedAt = '';
    public string $manualDescription = '';
    public ?int $manualClientId = null;
    public ?int $manualProjectId = null;
    public bool $manualIsBillable = true;

    public bool $showEditModal = false;
    public ?int $editingId = null;
    public int $editDurationMinutes = 0;
    public string $editStartedAt = '';
    #[Locked]
    public string $editOriginalStartedAt = '';
    #[Locked]
    public string $editOriginalStartedAtDisplay = '';
    public string $editDescription = '';
    public bool $editIsBillable = true;

    /**
     * The user's open timer, if one is currently running.
     */
    #[Computed]
    public function runningEntry(): ?TimeEntry
    {
        return TimeEntry::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    /**
     * The user's logged entries, newest first. Excludes only an open timer
     * (started but not yet ended); manual entries include an explicit start time.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TimeEntry>
     */
    #[Computed]
    public function entries()
    {
        return TimeEntry::query()
            ->with(['client', 'project'])
            ->where('user_id', Auth::id())
            ->where(fn ($query) => $query
                ->whereNotNull('ended_at')
                ->orWhereNull('started_at'))
            ->latest()
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string}>
     */
    #[Computed]
    public function clientOptions()
    {
        return Client::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'name' => $client->name]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string}>
     */
    #[Computed]
    public function projectOptions()
    {
        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $project): array => ['id' => $project->id, 'name' => $project->name]);
    }

    public function startTimer(StartTimer $startTimer): void
    {
        $result = $startTimer->execute(new StartTimerInput(
            projectId: $this->timerProjectId,
            clientId: $this->timerClientId,
            description: $this->timerDescription !== '' ? $this->timerDescription : null,
            isBillable: $this->timerIsBillable,
        ));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        $this->reset('timerDescription', 'timerClientId', 'timerProjectId');
        $this->timerIsBillable = true;
        unset($this->runningEntry);

        $this->success(__('Timer started.'));
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
            $this->error($this->firstError($result->errors));

            return;
        }

        unset($this->runningEntry, $this->entries);

        $this->success(__('Timer stopped.'));
    }

    public function recordManualEntry(RecordManualTimeEntry $recordManualTimeEntry): void
    {
        $this->validate([
            'manualStartedAt' => ['required', 'date_format:Y-m-d\\TH:i'],
            'manualDurationMinutes' => ['required', 'integer', 'min:1'],
            'manualDescription' => ['nullable', 'string', 'max:2000'],
        ]);

        $startedAt = $this->parseDatetimeLocal($this->manualStartedAt);
        $endedAt = $this->derivedEndAt($startedAt, $this->manualDurationMinutes);

        $result = $recordManualTimeEntry->execute(new RecordManualTimeEntryInput(
            durationMinutes: $this->manualDurationMinutes,
            projectId: $this->manualProjectId,
            clientId: $this->manualClientId,
            description: $this->manualDescription !== '' ? $this->manualDescription : null,
            isBillable: $this->manualIsBillable,
            startedAt: $startedAt->toDateTimeString(),
            endedAt: $endedAt->toDateTimeString(),
        ));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        $this->reset('manualDurationMinutes', 'manualStartedAt', 'manualDescription', 'manualClientId', 'manualProjectId');
        $this->manualIsBillable = true;
        unset($this->entries);

        $this->success(__('Time entry recorded.'));
    }

    public function editEntry(int $entryId): void
    {
        $entry = TimeEntry::query()
            ->where('user_id', Auth::id())
            ->findOrFail($entryId);

        $editStartedAt = $entry->started_at ?? $entry->created_at ?? now();

        $this->editingId = $entry->getKey();
        $this->editStartedAt = $this->datetimeLocalValue($editStartedAt);
        $this->editOriginalStartedAt = CarbonImmutable::instance($editStartedAt)->toDateTimeString();
        $this->editOriginalStartedAtDisplay = $this->editStartedAt;
        $this->editDurationMinutes = $entry->duration_minutes;
        $this->editDescription = $entry->description ?? '';
        $this->editIsBillable = $entry->is_billable;
        $this->showEditModal = true;
    }

    public function saveEntry(UpdateTimeEntry $updateTimeEntry): void
    {
        if ($this->editingId === null) {
            return;
        }

        $this->validate([
            'editStartedAt' => ['required', 'date_format:Y-m-d\\TH:i'],
            'editDurationMinutes' => ['required', 'integer', 'min:1'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
        ]);

        $startedAt = $this->editStartedAtValue();
        $endedAt = $this->derivedEndAt($startedAt, $this->editDurationMinutes);

        $result = $updateTimeEntry->execute(new UpdateTimeEntryInput(
            timeEntryId: $this->editingId,
            description: $this->editDescription !== '' ? $this->editDescription : null,
            durationMinutes: $this->editDurationMinutes,
            isBillable: $this->editIsBillable,
            startedAt: $startedAt->toDateTimeString(),
            endedAt: $endedAt->toDateTimeString(),
        ));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        $this->showEditModal = false;
        $this->editingId = null;
        $this->editStartedAt = '';
        $this->editOriginalStartedAt = '';
        $this->editOriginalStartedAtDisplay = '';
        unset($this->entries);

        $this->success(__('Time entry updated.'));
    }

    public function deleteEntry(int $entryId, DeleteTimeEntry $deleteTimeEntry): void
    {
        $result = $deleteTimeEntry->execute(new DeleteTimeEntryInput(timeEntryId: $entryId));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        unset($this->entries);

        $this->success(__('Time entry deleted.'));
    }

    private function parseDatetimeLocal(string $value): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $value);
    }

    private function datetimeLocalValue(?NativeDateTimeInterface $dateTime): string
    {
        return $dateTime === null
            ? ''
            : CarbonImmutable::instance($dateTime)->format('Y-m-d\\TH:i');
    }

    private function derivedEndAt(CarbonImmutable $startedAt, int $durationMinutes): CarbonImmutable
    {
        return $startedAt->addMinutes($durationMinutes);
    }

    private function editStartedAtValue(): CarbonImmutable
    {
        if (
            $this->editStartedAt === $this->editOriginalStartedAtDisplay
            && $this->editOriginalStartedAt !== ''
        ) {
            return CarbonImmutable::parse($this->editOriginalStartedAt);
        }

        return $this->parseDatetimeLocal($this->editStartedAt);
    }

    /**
     * Surface the first error an action returned for a toast.
     *
     * @param  array<int|string, mixed>  $errors
     */
    private function firstError(array $errors): string
    {
        $first = reset($errors);

        return is_string($first) ? $first : __('The action could not be completed.');
    }
}; ?>

<section class="w-full max-w-4xl space-y-8">
    <x-header :title="__('Time')" :subtitle="__('Track time as you work or log it after the fact')" separator />

    <x-card :title="__('Timer')">
        @if ($this->runningEntry)
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" data-test="running-timer">
                <div class="space-y-1">
                    <p class="font-medium">
                        {{ $this->runningEntry->description ?: __('Untitled timer') }}
                    </p>
                    <p class="text-sm text-base-content/60">
                        {{ __('Started :time', ['time' => $this->runningEntry->started_at->diffForHumans()]) }}
                    </p>
                </div>

                <x-button
                    label="{{ __('Stop timer') }}"
                    icon="o-stop-circle"
                    wire:click="stopTimer"
                    class="btn-error"
                    spinner="stopTimer"
                    data-test="stop-timer-button"
                />
            </div>
        @else
            <form wire:submit="startTimer" class="grid gap-4 sm:grid-cols-2">
                <x-input
                    wire:model="timerDescription"
                    label="{{ __('Description') }}"
                    placeholder="{{ __('What are you working on?') }}"
                    class="sm:col-span-2"
                    data-test="timer-description"
                />

                <x-select
                    wire:model="timerClientId"
                    label="{{ __('Client') }}"
                    placeholder="{{ __('No client') }}"
                    :options="$this->clientOptions"
                    data-test="timer-client"
                />

                <x-select
                    wire:model="timerProjectId"
                    label="{{ __('Project') }}"
                    placeholder="{{ __('No project') }}"
                    :options="$this->projectOptions"
                    data-test="timer-project"
                />

                <x-toggle
                    wire:model="timerIsBillable"
                    label="{{ __('Billable') }}"
                    data-test="timer-billable"
                />

                <div class="flex items-end justify-end">
                    <x-button
                        type="submit"
                        label="{{ __('Start timer') }}"
                        icon="o-play-circle"
                        class="btn-primary"
                        spinner="startTimer"
                        data-test="start-timer-button"
                    />
                </div>
            </form>
        @endif
    </x-card>

    <x-card :title="__('Log time')" :subtitle="__('Record a completed block of time')">
        <form wire:submit="recordManualEntry" class="grid gap-4 sm:grid-cols-2">
            <x-input
                wire:model="manualStartedAt"
                label="{{ __('Start') }}"
                type="datetime-local"
                required
                data-test="manual-started-at"
            />

            <x-input
                wire:model="manualDurationMinutes"
                label="{{ __('Duration (minutes)') }}"
                type="number"
                min="1"
                required
                data-test="manual-duration"
            />

            <x-input
                wire:model="manualDescription"
                label="{{ __('Description') }}"
                placeholder="{{ __('What did you do?') }}"
                data-test="manual-description"
            />

            <x-select
                wire:model="manualClientId"
                label="{{ __('Client') }}"
                placeholder="{{ __('No client') }}"
                :options="$this->clientOptions"
                data-test="manual-client"
            />

            <x-select
                wire:model="manualProjectId"
                label="{{ __('Project') }}"
                placeholder="{{ __('No project') }}"
                :options="$this->projectOptions"
                data-test="manual-project"
            />

            <x-toggle
                wire:model="manualIsBillable"
                label="{{ __('Billable') }}"
                data-test="manual-billable"
            />

            <div class="flex items-end justify-end">
                <x-button
                    type="submit"
                    label="{{ __('Log time') }}"
                    class="btn-primary"
                    spinner="recordManualEntry"
                    data-test="record-manual-button"
                />
            </div>
        </form>
    </x-card>

    <div class="space-y-2">
        <h2 class="text-sm font-medium text-base-content/70">{{ __('Your entries') }}</h2>

        @if ($this->entries->isEmpty())
            <x-card>
                <p class="text-center text-base-content/60">{{ __('No time logged yet.') }}</p>
            </x-card>
        @else
            <div class="overflow-x-auto">
            <table class="table" data-test="entries-table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Client') }}</th>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Duration') }}</th>
                        <th>{{ __('Billable') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->entries as $entry)
                        <tr wire:key="entry-{{ $entry->id }}">
                            <td>{{ $entry->description ?: __('Untitled') }}</td>
                            <td data-test="entry-date-{{ $entry->id }}">{{ $entry->started_at?->toDateString() ?? '—' }}</td>
                            <td class="text-base-content/70">{{ $entry->client?->name ?? '—' }}</td>
                            <td class="text-base-content/70">{{ $entry->project?->name ?? '—' }}</td>
                            <td class="font-mono">{{ \App\Services\Support\DurationFormatter::minutes($entry->duration_minutes) }}</td>
                            <td>
                                @if ($entry->is_billable)
                                    <x-badge :value="__('Billable')" class="badge-soft badge-success" />
                                @else
                                    <x-badge :value="__('Non-billable')" class="badge-soft" />
                                @endif
                            </td>
                            <td class="flex justify-end gap-1">
                                <x-button
                                    icon="o-pencil-square"
                                    wire:click="editEntry({{ $entry->id }})"
                                    class="btn-ghost btn-sm"
                                    :title="__('Edit')"
                                    data-test="edit-entry-{{ $entry->id }}"
                                />
                                <x-button
                                    icon="o-trash"
                                    wire:click="deleteEntry({{ $entry->id }})"
                                    wire:confirm="{{ __('Delete this time entry?') }}"
                                    class="btn-ghost btn-sm text-error"
                                    :title="__('Delete')"
                                    data-test="delete-entry-{{ $entry->id }}"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    <x-modal wire:model="showEditModal" :title="__('Edit time entry')" class="backdrop-blur">
        <form wire:submit="saveEntry" class="space-y-4">
            <x-input
                wire:model="editDescription"
                label="{{ __('Description') }}"
                placeholder="{{ __('What did you do?') }}"
                data-test="edit-description"
            />

            <x-input
                wire:model="editStartedAt"
                label="{{ __('Start') }}"
                type="datetime-local"
                required
                data-test="edit-started-at"
            />

            <x-input
                wire:model="editDurationMinutes"
                label="{{ __('Duration (minutes)') }}"
                type="number"
                min="1"
                required
                data-test="edit-duration"
            />

            <x-toggle
                wire:model="editIsBillable"
                label="{{ __('Billable') }}"
                data-test="edit-billable"
            />

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showEditModal', false)" />
                <x-button
                    type="submit"
                    label="{{ __('Save') }}"
                    class="btn-primary"
                    data-test="save-entry-button"
                />
            </x-slot:actions>
        </form>
    </x-modal>
</section>

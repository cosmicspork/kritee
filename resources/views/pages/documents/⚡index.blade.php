<?php

use App\Actions\Document\DeleteDocument;
use App\Actions\Document\DeleteDocumentInput;
use App\Models\Document;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('Documents')] class extends Component {
    use Toast, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Document>
     */
    #[Computed]
    public function documents()
    {
        return Document::query()
            ->with(['client', 'uploadedBy'])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }

    /**
     * Remove a document through the action layer, surfacing any failure as a toast.
     */
    public function delete(DeleteDocument $action, int $document): void
    {
        $result = $action->execute(DeleteDocumentInput::validateAndCreate([
            'document_id' => $document,
        ]));

        if (! $result->success) {
            $this->error($this->firstError($result->errors));

            return;
        }

        unset($this->documents);

        $this->success(__('Document deleted.'));
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
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Documents') }}</h1>
            <p class="text-sm text-base-content/70">{{ __('Notes and files shared across client work.') }}</p>
        </div>

        <x-button
            :label="__('New document')"
            icon="o-plus"
            :link="route('documents.create')"
            class="btn-primary"
            data-test="new-document-button"
        />
    </div>

    <x-input
        wire:model.live.debounce.300ms="search"
        :label="__('Search')"
        icon="o-magnifying-glass"
        :placeholder="__('Filter by title')"
        clearable
        data-test="document-search"
    />

    @if ($this->documents->isEmpty())
        <x-card class="text-center">
            <x-icon name="o-document" class="mx-auto mb-2 h-10 w-10 text-base-content/40" />
            <p class="text-base-content/70">{{ __('No documents yet.') }}</p>
        </x-card>
    @else
        <table class="table" data-test="documents-table">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Uploaded by') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->documents as $document)
                    <tr wire:key="document-{{ $document->id }}">
                        <td>
                            <a href="{{ route('documents.show', $document) }}" wire:navigate class="link link-hover font-medium">
                                {{ $document->title }}
                            </a>
                        </td>
                        <td class="text-base-content/70">{{ $document->client?->name ?? '—' }}</td>
                        <td class="text-base-content/70">{{ $document->uploadedBy?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap text-base-content/70">{{ $document->created_at->format('M j, Y') }}</td>
                        <td class="text-end">
                            <div class="flex justify-end gap-1">
                                @can('update', $document)
                                    <x-button
                                        icon="o-pencil-square"
                                        :link="route('documents.edit', $document)"
                                        class="btn-ghost btn-sm"
                                        :title="__('Edit')"
                                        data-test="edit-document-{{ $document->id }}"
                                    />
                                @endcan
                                @can('delete', $document)
                                    <x-button
                                        icon="o-trash"
                                        wire:click="delete({{ $document->id }})"
                                        wire:confirm="{{ __('Delete this document?') }}"
                                        class="btn-ghost btn-sm text-error"
                                        :title="__('Delete')"
                                        data-test="delete-document-{{ $document->id }}"
                                    />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div>{{ $this->documents->links() }}</div>
    @endif
</div>

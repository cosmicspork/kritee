<?php

use App\Actions\Document\CreateDocument;
use App\Actions\Document\CreateDocumentInput;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new #[Layout('layouts::app'), Title('New document')] class extends Component {
    use Toast, WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public ?string $content = null;

    public ?int $clientId = null;

    #[Validate('nullable|file|mimes:pdf,png,jpg,jpeg,webp,txt,docx|max:10240')]
    public $file = null;

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
     * Persist a new document through the action layer.
     */
    public function save(CreateDocument $action): void
    {
        $this->validate();

        $result = $action->execute(CreateDocumentInput::validateAndCreate([
            'uploaded_by' => Auth::id(),
            'title' => $this->title,
            'content' => $this->content ?: null,
            'client_id' => $this->clientId ?: null,
            'file' => $this->filePayload(),
        ]));

        if (! $result->success) {
            $this->mapErrors($result->errors);

            return;
        }

        $this->success(__('Document created.'), redirectTo: route('documents.show', $result->data));
    }

    /**
     * Stores the upload on the public disk and returns the metadata the action persists,
     * or null when no file was attached.
     *
     * @return array{filename: string, path: string, mime_type: string, size_bytes: int}|null
     */
    private function filePayload(): ?array
    {
        if ($this->file === null) {
            return null;
        }

        $path = $this->file->store('documents', 'public');

        return [
            'filename' => $this->file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $this->file->getSize() ?: 0,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function mapErrors(array $errors): void
    {
        foreach ($errors as $field => $message) {
            $this->addError(is_string($field) ? $field : 'document', is_string($message) ? $message : __('Invalid value.'));
        }

        $first = collect($errors)->flatten()->first();
        $this->error(is_string($first) ? $first : __('Could not create the document.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">{{ __('New document') }}</h1>
        <x-button :label="__('Back')" icon="o-arrow-left" :link="route('documents.index')" class="btn-ghost" />
    </div>

    <x-card>
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-input
                wire:model="title"
                :label="__('Title')"
                required
                data-test="document-title"
            />

            <x-textarea
                wire:model="content"
                :label="__('Content')"
                :placeholder="__('Write the document body…')"
                rows="8"
                data-test="document-content"
            />

            <x-select
                wire:model="clientId"
                :label="__('Client')"
                :options="$this->clientOptions()"
                data-test="document-client"
            />

            <x-file
                wire:model="file"
                :label="__('Attachment')"
                :hint="__('PDF, image, text or Word doc, up to 10MB')"
                accept="application/pdf,image/*,.txt,.docx"
                data-test="document-file"
            />

            <div class="flex justify-end gap-2">
                <x-button :label="__('Cancel')" :link="route('documents.index')" class="btn-ghost" />
                <x-button type="submit" :label="__('Create document')" class="btn-primary" spinner="save" data-test="save-document-button" />
            </div>
        </form>
    </x-card>
</div>

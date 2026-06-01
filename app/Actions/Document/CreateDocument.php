<?php

namespace App\Actions\Document;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\DocumentCreated;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class CreateDocument implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  CreateDocumentInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A document must be created by a user.']);
        }

        if ($actor->user()->cannot('create', Document::class)) {
            return ActionResult::failure(['authorization' => 'You may not create documents.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $actor): ActionResult {
            $document = DB::transaction(function () use ($input, $actor): Document {
                $document = Document::create([
                    'title' => $input->title,
                    'content' => $input->content,
                    'client_id' => $input->clientId,
                    'uploaded_by' => $input->uploadedBy,
                ]);

                $this->attachFile($document, $input->file, $actor->user());

                return $document;
            });

            DocumentCreated::dispatch($document, $actor->id());

            return ActionResult::success($document->fresh());
        });
    }

    private function attachFile(Document $document, DocumentFileData|Optional|null $file, User $uploader): void
    {
        if (! $file instanceof DocumentFileData) {
            return;
        }

        $document->attachments()->create([
            'uploaded_by' => $uploader->getKey(),
            'filename' => $file->filename,
            'path' => $file->path,
            'mime_type' => $file->mimeType,
            'size_bytes' => $file->sizeBytes,
        ]);
    }
}

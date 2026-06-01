<?php

namespace App\Actions\Document;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdateDocument implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  UpdateDocumentInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A document must be updated by a user.']);
        }

        $document = Document::find($input->documentId);

        if ($document === null) {
            return ActionResult::failure(['document_id' => 'Document not found.']);
        }

        if ($actor->user()->cannot('update', $document)) {
            return ActionResult::failure(['authorization' => 'You may not update this document.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $document): ActionResult {
            $changes = $this->changes($input);

            DB::transaction(function () use ($document, $changes): void {
                if ($changes !== []) {
                    $document->update($changes);
                }
            });

            return ActionResult::success($document->fresh());
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(UpdateDocumentInput $input): array
    {
        $candidates = [
            'title' => $input->title,
            'content' => $input->content,
            'client_id' => $input->clientId,
        ];

        return array_filter(
            $candidates,
            fn (mixed $value): bool => ! $value instanceof Optional,
        );
    }
}

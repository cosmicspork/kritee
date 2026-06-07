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

class DeleteDocument implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    /**
     * @param  DeleteDocumentInput  $input
     */
    public function execute(ActionInput $input): ActionResult
    {
        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A document must be deleted by a user.']);
        }

        $document = Document::find($input->documentId);

        if ($document === null) {
            return ActionResult::failure(['document_id' => 'Document not found.']);
        }

        if ($actor->user()->cannot('delete', $document)) {
            return ActionResult::failure(['authorization' => 'You may not delete this document.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($document): ActionResult {
            DB::transaction(function () use ($document): void {
                $document->attachments()->delete();
                $document->delete();
            });

            return ActionResult::success(['document_id' => $document->getKey()]);
        });
    }
}

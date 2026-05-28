<?php

namespace App\Actions\Contact;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ContactDeleted;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteContact implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof DeleteContactInput) {
            throw new InvalidArgumentException(self::class.' requires a '.DeleteContactInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A contact may only be deleted by a user.',
            ]);
        }

        $contact = Contact::query()->findOrFail($input->contactId);

        if ($this->actor->user()->cannot('delete', $contact)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to delete this contact.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($contact): ActionResult {
            $contactId = (int) $contact->getKey();
            $clientId = (int) $contact->client_id;

            DB::transaction(fn () => $contact->delete());

            ContactDeleted::dispatch($contactId, $clientId);

            return ActionResult::success(['contact_id' => $contactId, 'client_id' => $clientId]);
        });
    }
}

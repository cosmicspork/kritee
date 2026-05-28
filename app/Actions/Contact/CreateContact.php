<?php

namespace App\Actions\Contact;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ContactCreated;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateContact implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof CreateContactInput) {
            throw new InvalidArgumentException(self::class.' requires a '.CreateContactInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A contact may only be created by a user.',
            ]);
        }

        if ($this->actor->user()->cannot('create', Contact::class)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to create contacts.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            $contact = DB::transaction(function () use ($input): Contact {
                $contact = Contact::create([
                    'client_id' => $input->clientId,
                    'name' => $input->name,
                    'email' => $input->email,
                    'phone' => $input->phone,
                    'title' => $input->title,
                    'is_primary' => $input->isPrimary,
                    'notes' => $input->notes,
                ]);

                if ($input->isPrimary) {
                    $this->demoteOtherPrimaries($contact);
                }

                return $contact;
            });

            ContactCreated::dispatch($contact);

            return ActionResult::success($contact);
        });
    }

    private function demoteOtherPrimaries(Contact $contact): void
    {
        Contact::query()
            ->where('client_id', $contact->client_id)
            ->whereKeyNot($contact->getKey())
            ->update(['is_primary' => false]);
    }
}

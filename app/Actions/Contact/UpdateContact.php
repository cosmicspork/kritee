<?php

namespace App\Actions\Contact;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ContactUpdated;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\LaravelData\Optional;

class UpdateContact implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof UpdateContactInput) {
            throw new InvalidArgumentException(self::class.' requires an '.UpdateContactInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A contact may only be updated by a user.',
            ]);
        }

        $contact = Contact::query()->findOrFail($input->contactId);

        if ($this->actor->user()->cannot('update', $contact)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to update this contact.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $contact): ActionResult {
            $contact = DB::transaction(function () use ($input, $contact): Contact {
                $contact->fill($this->changedAttributes($input));
                $becomingPrimary = $contact->is_primary && $contact->isDirty('is_primary');
                $contact->save();

                if ($becomingPrimary) {
                    Contact::query()
                        ->where('client_id', $contact->client_id)
                        ->whereKeyNot($contact->getKey())
                        ->update(['is_primary' => false]);
                }

                return $contact;
            });

            ContactUpdated::dispatch($contact);

            return ActionResult::success($contact);
        });
    }

    /**
     * Collect only the attributes the caller actually supplied, leaving
     * {@see Optional} fields untouched.
     *
     * @return array<string, mixed>
     */
    private function changedAttributes(UpdateContactInput $input): array
    {
        $candidates = [
            'name' => $input->name,
            'email' => $input->email,
            'phone' => $input->phone,
            'title' => $input->title,
            'is_primary' => $input->isPrimary,
            'notes' => $input->notes,
        ];

        return array_filter(
            $candidates,
            fn (mixed $value): bool => ! $value instanceof Optional,
        );
    }
}

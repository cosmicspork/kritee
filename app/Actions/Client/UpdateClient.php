<?php

namespace App\Actions\Client;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ClientUpdated;
use App\Models\Client;
use App\Services\Support\SlugGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateClient implements Action
{
    use EnsuresIdempotency;

    public function __construct(
        private readonly Actor $actor,
        private readonly SlugGenerator $slugs,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        assert($input instanceof UpdateClientInput);

        $client = Client::findOrFail($input->clientId);

        if (! $this->authorized($client)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to update this client.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $client): ActionResult {
            return DB::transaction(function () use ($input, $client): ActionResult {
                $client->fill([
                    'name' => $input->name,
                    'email' => $input->email,
                    'phone' => $input->phone,
                    'address' => $input->address,
                    'notes' => $input->notes,
                ]);

                if ($client->isDirty('name')) {
                    $client->slug = $this->slugs->unique(
                        Client::class,
                        $input->name,
                        ignoreKey: $client->getKey(),
                    );
                }

                $client->save();

                ClientUpdated::dispatch($client);

                return ActionResult::success($client);
            });
        });
    }

    private function authorized(Client $client): bool
    {
        if (! $this->actor instanceof UserActor) {
            return true;
        }

        try {
            Gate::forUser($this->actor->user())->authorize('update', $client);
        } catch (AuthorizationException) {
            return false;
        }

        return true;
    }
}

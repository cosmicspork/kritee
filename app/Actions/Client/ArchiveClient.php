<?php

namespace App\Actions\Client;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ClientStatus;
use App\Events\ClientArchived;
use App\Models\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArchiveClient implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        assert($input instanceof ArchiveClientInput);

        $client = Client::findOrFail($input->clientId);

        if (! $this->authorized($client)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to archive this client.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($client): ActionResult {
            return DB::transaction(function () use ($client): ActionResult {
                if ($client->status !== ClientStatus::Archived) {
                    $client->status = ClientStatus::Archived;
                    $client->save();

                    ClientArchived::dispatch($client);
                }

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
            Gate::forUser($this->actor->user())->authorize('archive', $client);
        } catch (AuthorizationException) {
            return false;
        }

        return true;
    }
}

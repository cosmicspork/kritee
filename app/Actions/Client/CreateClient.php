<?php

namespace App\Actions\Client;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ClientStatus;
use App\Events\ClientCreated;
use App\Models\Client;
use App\Services\Support\SlugGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateClient implements Action
{
    use EnsuresIdempotency;

    public function __construct(
        private readonly Actor $actor,
        private readonly SlugGenerator $slugs,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        assert($input instanceof CreateClientInput);

        if (! $this->authorized()) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to create clients.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            return DB::transaction(function () use ($input): ActionResult {
                $client = Client::create([
                    'name' => $input->name,
                    'slug' => $this->slugs->unique(Client::class, $input->name),
                    'email' => $input->email,
                    'phone' => $input->phone,
                    'address' => $input->address,
                    'status' => ClientStatus::Active,
                    'notes' => $input->notes,
                ]);

                ClientCreated::dispatch($client);

                return ActionResult::success($client);
            });
        });
    }

    private function authorized(): bool
    {
        if (! $this->actor instanceof UserActor) {
            return true;
        }

        try {
            Gate::forUser($this->actor->user())->authorize('create', Client::class);
        } catch (AuthorizationException) {
            return false;
        }

        return true;
    }
}

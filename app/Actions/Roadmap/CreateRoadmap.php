<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\RoadmapStatus;
use App\Events\RoadmapCreated;
use App\Models\Roadmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateRoadmap implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof CreateRoadmapInput) {
            return ActionResult::failure(['input' => 'Expected a CreateRoadmapInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to create a roadmap.']);
        }

        if (! Gate::forUser($actor->user())->allows('create', Roadmap::class)) {
            return ActionResult::failure(['authorization' => 'Not authorized to create a roadmap.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            $roadmap = DB::transaction(fn (): Roadmap => Roadmap::create([
                'title' => $input->title,
                'description' => $input->description,
                'status' => RoadmapStatus::Active,
                'client_id' => $input->clientId,
                'is_public' => $input->isPublic,
            ]));

            RoadmapCreated::dispatch($roadmap);

            return ActionResult::success($roadmap);
        });
    }
}

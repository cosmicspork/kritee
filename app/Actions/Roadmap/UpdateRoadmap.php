<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\RoadmapUpdated;
use App\Models\Roadmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

class UpdateRoadmap implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof UpdateRoadmapInput) {
            return ActionResult::failure(['input' => 'Expected an UpdateRoadmapInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to update a roadmap.']);
        }

        $roadmap = Roadmap::find($input->roadmapId);

        if ($roadmap === null) {
            return ActionResult::failure(['roadmap_id' => 'Roadmap not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('update', $roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to update this roadmap.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $roadmap): ActionResult {
            $changes = $this->changes($input);

            DB::transaction(function () use ($roadmap, $changes): void {
                if ($changes !== []) {
                    $roadmap->fill($changes)->save();
                }
            });

            RoadmapUpdated::dispatch($roadmap);

            return ActionResult::success($roadmap);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(UpdateRoadmapInput $input): array
    {
        $changes = [];

        if (! $input->title instanceof Optional) {
            $changes['title'] = $input->title;
        }

        if (! $input->description instanceof Optional) {
            $changes['description'] = $input->description;
        }

        if (! $input->clientId instanceof Optional) {
            $changes['client_id'] = $input->clientId;
        }

        return $changes;
    }
}

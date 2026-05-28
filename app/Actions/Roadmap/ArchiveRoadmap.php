<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\RoadmapStatus;
use App\Events\RoadmapArchived;
use App\Models\Roadmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArchiveRoadmap implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof ArchiveRoadmapInput) {
            return ActionResult::failure(['input' => 'Expected an ArchiveRoadmapInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to archive a roadmap.']);
        }

        $roadmap = Roadmap::find($input->roadmapId);

        if ($roadmap === null) {
            return ActionResult::failure(['roadmap_id' => 'Roadmap not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('archive', $roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to archive this roadmap.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($roadmap): ActionResult {
            DB::transaction(function () use ($roadmap): void {
                $roadmap->status = RoadmapStatus::Archived;
                $roadmap->save();
            });

            RoadmapArchived::dispatch($roadmap);

            return ActionResult::success($roadmap);
        });
    }
}

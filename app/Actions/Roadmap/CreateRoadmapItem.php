<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\RoadmapItemCreated;
use App\Models\Roadmap;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateRoadmapItem implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof CreateRoadmapItemInput) {
            return ActionResult::failure(['input' => 'Expected a CreateRoadmapItemInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to create a roadmap item.']);
        }

        $roadmap = Roadmap::find($input->roadmapId);

        if ($roadmap === null) {
            return ActionResult::failure(['roadmap_id' => 'Roadmap not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('manageItems', $roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to add items to this roadmap.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $roadmap): ActionResult {
            $item = DB::transaction(function () use ($input, $roadmap): RoadmapItem {
                $item = new RoadmapItem([
                    'title' => $input->title,
                    'description' => $input->description,
                    'status' => $input->status,
                    'starts_at' => $input->startsAt,
                    'ends_at' => $input->endsAt,
                    'sort_order' => $this->nextSortOrder($roadmap),
                    'is_public' => $input->isPublic,
                ]);
                $item->roadmap()->associate($roadmap);
                $item->save();

                return $item;
            });

            RoadmapItemCreated::dispatch($item);

            return ActionResult::success($item);
        });
    }

    private function nextSortOrder(Roadmap $roadmap): int
    {
        return (int) $roadmap->items()->max('sort_order') + 1;
    }
}

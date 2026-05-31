<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\RoadmapItemPublicityChanged;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ToggleRoadmapItemPublic implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof ToggleRoadmapItemPublicInput) {
            return ActionResult::failure(['input' => 'Expected a ToggleRoadmapItemPublicInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to change roadmap item visibility.']);
        }

        $item = RoadmapItem::with('roadmap')->find($input->roadmapItemId);

        if ($item === null) {
            return ActionResult::failure(['roadmap_item_id' => 'Roadmap item not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('manageItems', $item->roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to change this roadmap item.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $item): ActionResult {
            DB::transaction(function () use ($input, $item): void {
                $item->is_public = $input->isPublic;
                $item->save();
            });

            RoadmapItemPublicityChanged::dispatch($item);

            return ActionResult::success($item);
        });
    }
}

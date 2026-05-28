<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\RoadmapItemMoved;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MoveRoadmapItem implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof MoveRoadmapItemInput) {
            return ActionResult::failure(['input' => 'Expected a MoveRoadmapItemInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to move a roadmap item.']);
        }

        $item = RoadmapItem::with('roadmap')->find($input->roadmapItemId);

        if ($item === null) {
            return ActionResult::failure(['roadmap_item_id' => 'Roadmap item not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('manageItems', $item->roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to reorder this roadmap item.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $item): ActionResult {
            DB::transaction(function () use ($input, $item): void {
                $this->resequence($item, $input->sortOrder);
            });

            RoadmapItemMoved::dispatch($item);

            return ActionResult::success($item->refresh());
        });
    }

    /**
     * Drop the item at the requested position among its siblings and renumber
     * the whole roadmap from zero so the stored order has no gaps or ties.
     */
    private function resequence(RoadmapItem $item, int $target): void
    {
        $siblings = RoadmapItem::query()
            ->where('roadmap_id', $item->roadmap_id)
            ->whereKeyNot($item->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $position = max(0, min($target, $siblings->count()));
        $siblings->splice($position, 0, [$item]);

        $siblings->values()->each(function (RoadmapItem $sibling, int $index): void {
            if ($sibling->sort_order !== $index) {
                $sibling->sort_order = $index;
                $sibling->save();
            }
        });
    }
}

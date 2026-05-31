<?php

namespace App\Actions\Roadmap;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Events\RoadmapItemUpdated;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

class UpdateRoadmapItem implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof UpdateRoadmapItemInput) {
            return ActionResult::failure(['input' => 'Expected an UpdateRoadmapItemInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to update a roadmap item.']);
        }

        $item = RoadmapItem::with('roadmap')->find($input->roadmapItemId);

        if ($item === null) {
            return ActionResult::failure(['roadmap_item_id' => 'Roadmap item not found.']);
        }

        if (! Gate::forUser($actor->user())->allows('manageItems', $item->roadmap)) {
            return ActionResult::failure(['authorization' => 'Not authorized to update this roadmap item.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $item): ActionResult {
            $changes = $this->changes($input);

            DB::transaction(function () use ($item, $changes): void {
                if ($changes !== []) {
                    $item->fill($changes)->save();
                }
            });

            RoadmapItemUpdated::dispatch($item);

            return ActionResult::success($item);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(UpdateRoadmapItemInput $input): array
    {
        $changes = [];

        if (! $input->title instanceof Optional) {
            $changes['title'] = $input->title;
        }

        if (! $input->description instanceof Optional) {
            $changes['description'] = $input->description;
        }

        if (! $input->status instanceof Optional) {
            $changes['status'] = $input->status;
        }

        if (! $input->startsAt instanceof Optional) {
            $changes['starts_at'] = $input->startsAt;
        }

        if (! $input->endsAt instanceof Optional) {
            $changes['ends_at'] = $input->endsAt;
        }

        return $changes;
    }
}

<?php

namespace App\Actions\Linkable;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\LinkRelationshipType;
use App\Events\LinkCreated;
use App\Models\Linkable;
use Illuminate\Support\Facades\DB;

/**
 * Establish a directed link between two records.
 *
 * Asymmetric relationship types carry a complementary edge: `blocks` implies the
 * target is `blocked_by` the source, and `duplicates` implies `duplicated_by`.
 * That inverse is persisted in the same transaction so the graph is never left
 * half-described. `relates_to` is symmetric and queried bidirectionally, so it
 * needs no second row.
 */
class CreateLink implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof CreateLinkInput) {
            return ActionResult::failure(['input' => 'Expected CreateLinkInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to create links.']);
        }

        if ($actor->user()->cannot('create', Linkable::class)) {
            return ActionResult::failure(['authorization' => 'You may not create links.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            $result = DB::transaction(function () use ($input): array {
                $link = Linkable::create([
                    'source_type' => $input->sourceType,
                    'source_id' => $input->sourceId,
                    'target_type' => $input->targetType,
                    'target_id' => $input->targetId,
                    'relationship_type' => $input->relationshipType,
                    'note' => $input->note,
                ]);

                $inverse = $this->inverseOf($input->relationshipType);

                $inverseLink = $inverse === null ? null : Linkable::create([
                    'source_type' => $input->targetType,
                    'source_id' => $input->targetId,
                    'target_type' => $input->sourceType,
                    'target_id' => $input->sourceId,
                    'relationship_type' => $inverse,
                    'note' => $input->note,
                ]);

                return ['link' => $link, 'inverse' => $inverseLink];
            });

            LinkCreated::dispatch($result['link'], $result['inverse']);

            return ActionResult::success($result);
        });
    }

    /**
     * The complementary edge an asymmetric relationship implies, or null when
     * the relationship is symmetric and needs no second row.
     */
    private function inverseOf(LinkRelationshipType $type): ?LinkRelationshipType
    {
        return match ($type) {
            LinkRelationshipType::Blocks => LinkRelationshipType::BlockedBy,
            LinkRelationshipType::BlockedBy => LinkRelationshipType::Blocks,
            LinkRelationshipType::Duplicates => LinkRelationshipType::DuplicatedBy,
            LinkRelationshipType::DuplicatedBy => LinkRelationshipType::Duplicates,
            LinkRelationshipType::RelatesTo => null,
        };
    }
}

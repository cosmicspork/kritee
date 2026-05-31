<?php

namespace App\Actions\Linkable;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Concerns\ResolvesActor;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\UserActor;
use App\Enums\LinkRelationshipType;
use App\Events\LinkRemoved;
use App\Models\Linkable;
use Illuminate\Support\Facades\DB;

/**
 * Tear down a link and the complementary edge an asymmetric relationship created
 * alongside it, so removal mirrors creation and never strands a one-sided edge.
 */
class RemoveLink implements Action
{
    use EnsuresIdempotency;
    use ResolvesActor;

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof RemoveLinkInput) {
            return ActionResult::failure(['input' => 'Expected RemoveLinkInput.']);
        }

        $actor = $this->actor();

        if (! $actor instanceof UserActor) {
            return ActionResult::failure(['actor' => 'A user is required to remove links.']);
        }

        $link = $this->locate($input, $input->relationshipType);

        if ($link === null) {
            return ActionResult::failure(['link' => 'The link does not exist.']);
        }

        if ($actor->user()->cannot('delete', $link)) {
            return ActionResult::failure(['authorization' => 'You may not remove this link.']);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $link): ActionResult {
            $inverse = $this->inverseOf($input->relationshipType);
            $inverseLink = $inverse === null ? null : $this->locateInverse($input, $inverse);

            DB::transaction(function () use ($link, $inverseLink): void {
                $link->delete();
                $inverseLink?->delete();
            });

            LinkRemoved::dispatch(
                $this->coordinates($link),
                $inverseLink === null ? null : $this->coordinates($inverseLink),
            );

            return ActionResult::success([
                'link' => $this->coordinates($link),
                'inverse' => $inverseLink === null ? null : $this->coordinates($inverseLink),
            ]);
        });
    }

    private function locate(RemoveLinkInput $input, LinkRelationshipType $type): ?Linkable
    {
        return Linkable::query()
            ->where('source_type', $input->sourceType)
            ->where('source_id', $input->sourceId)
            ->where('target_type', $input->targetType)
            ->where('target_id', $input->targetId)
            ->where('relationship_type', $type)
            ->first();
    }

    /**
     * The inverse edge stores the same pair with source and target swapped.
     */
    private function locateInverse(RemoveLinkInput $input, LinkRelationshipType $type): ?Linkable
    {
        return Linkable::query()
            ->where('source_type', $input->targetType)
            ->where('source_id', $input->targetId)
            ->where('target_type', $input->sourceType)
            ->where('target_id', $input->sourceId)
            ->where('relationship_type', $type)
            ->first();
    }

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

    /**
     * @return array{
     *     source_type: string,
     *     source_id: int|string,
     *     target_type: string,
     *     target_id: int|string,
     *     relationship_type: string,
     * }
     */
    private function coordinates(Linkable $link): array
    {
        return [
            'source_type' => $link->source_type,
            'source_id' => $link->source_id,
            'target_type' => $link->target_type,
            'target_id' => $link->target_id,
            'relationship_type' => $link->relationship_type->value,
        ];
    }
}

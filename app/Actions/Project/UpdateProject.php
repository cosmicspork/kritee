<?php

namespace App\Actions\Project;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Events\ProjectUpdated;
use App\Models\Project;
use App\Services\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\LaravelData\Optional;

class UpdateProject implements Action
{
    use EnsuresIdempotency;

    public function __construct(
        private readonly Actor $actor,
        private readonly SlugGenerator $slugs,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof UpdateProjectInput) {
            throw new InvalidArgumentException(self::class.' requires an '.UpdateProjectInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A project must be updated by a user.',
            ]);
        }

        $user = $this->actor->user();
        $project = Project::query()->findOrFail($input->projectId);

        if ($user->cannot('update', $project)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to update this project.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input, $project): ActionResult {
            $changes = $this->changedAttributes($input, $project);

            DB::transaction(function () use ($project, $changes): void {
                $project->fill($changes)->save();
            });

            ProjectUpdated::dispatch($project->refresh());

            return ActionResult::success($project);
        });
    }

    /**
     * Map the supplied (non-{@see Optional}) input fields onto the columns to
     * persist, regenerating the slug only when the name actually changes.
     *
     * @return array<string, mixed>
     */
    private function changedAttributes(UpdateProjectInput $input, Project $project): array
    {
        $changes = [];

        if (! $input->name instanceof Optional) {
            $changes['name'] = $input->name;

            if ($input->name !== $project->name) {
                $changes['slug'] = $this->slugs->unique(Project::class, $input->name, ignoreKey: $project->getKey());
            }
        }

        if (! $input->clientId instanceof Optional) {
            $changes['client_id'] = $input->clientId;
        }

        if (! $input->description instanceof Optional) {
            $changes['description'] = $input->description;
        }

        if (! $input->status instanceof Optional) {
            $changes['status'] = $input->status;
        }

        if (! $input->budget instanceof Optional) {
            $changes['budget'] = $input->budget;
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

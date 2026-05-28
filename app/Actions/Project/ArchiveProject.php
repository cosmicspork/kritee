<?php

namespace App\Actions\Project;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ProjectStatus;
use App\Events\ProjectArchived;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ArchiveProject implements Action
{
    use EnsuresIdempotency;

    public function __construct(private readonly Actor $actor) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof ArchiveProjectInput) {
            throw new InvalidArgumentException(self::class.' requires an '.ArchiveProjectInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A project must be archived by a user.',
            ]);
        }

        $user = $this->actor->user();
        $project = Project::query()->findOrFail($input->projectId);

        if ($user->cannot('archive', $project)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to archive this project.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($project): ActionResult {
            DB::transaction(function () use ($project): void {
                $project->status = ProjectStatus::Archived;
                $project->save();
            });

            ProjectArchived::dispatch($project);

            return ActionResult::success($project);
        });
    }
}

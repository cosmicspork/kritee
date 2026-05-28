<?php

namespace App\Actions\Project;

use App\Actions\Concerns\EnsuresIdempotency;
use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Models\Project;
use App\Services\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateProject implements Action
{
    use EnsuresIdempotency;

    public function __construct(
        private readonly Actor $actor,
        private readonly SlugGenerator $slugs,
    ) {}

    public function execute(ActionInput $input): ActionResult
    {
        if (! $input instanceof CreateProjectInput) {
            throw new InvalidArgumentException(self::class.' requires a '.CreateProjectInput::class.'.');
        }

        if (! $this->actor instanceof UserActor) {
            return ActionResult::failure([
                'actor' => 'A project must be created by a user.',
            ]);
        }

        $user = $this->actor->user();

        if ($user->cannot('create', Project::class)) {
            return ActionResult::failure([
                'authorization' => 'You are not allowed to create projects.',
            ]);
        }

        return $this->idempotently($input->idempotencyKey, function () use ($input): ActionResult {
            $project = DB::transaction(fn (): Project => Project::create([
                'client_id' => $input->clientId,
                'name' => $input->name,
                'slug' => $this->slugs->unique(Project::class, $input->name),
                'description' => $input->description,
                'status' => $input->status ?? ProjectStatus::Active,
                'budget' => $input->budget,
                'starts_at' => $input->startsAt,
                'ends_at' => $input->endsAt,
            ]));

            ProjectCreated::dispatch($project);

            return ActionResult::success($project);
        });
    }
}

<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

final class ProjectCreated implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Project $project) {}
}

<?php

namespace App\Events;

use App\Models\Roadmap;
use Illuminate\Foundation\Events\Dispatchable;

final class RoadmapArchived implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly Roadmap $roadmap) {}
}

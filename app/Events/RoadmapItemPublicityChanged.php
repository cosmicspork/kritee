<?php

namespace App\Events;

use App\Models\RoadmapItem;
use Illuminate\Foundation\Events\Dispatchable;

final class RoadmapItemPublicityChanged implements DomainEvent
{
    use Dispatchable;

    public function __construct(public readonly RoadmapItem $item) {}
}

<?php

namespace App\Enums;

enum RoadmapItemStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
            self::InProgress => __('In progress'),
            self::Completed => __('Completed'),
        };
    }
}

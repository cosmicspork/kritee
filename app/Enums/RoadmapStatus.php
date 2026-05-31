<?php

namespace App\Enums;

enum RoadmapStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Archived => __('Archived'),
        };
    }
}

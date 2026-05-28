<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::OnHold => __('On hold'),
            self::Completed => __('Completed'),
            self::Archived => __('Archived'),
        };
    }
}

<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => __('Low'),
            self::Medium => __('Medium'),
            self::High => __('High'),
            self::Urgent => __('Urgent'),
        };
    }
}

<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Done = 'done';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::InProgress => __('In progress'),
            self::InReview => __('In review'),
            self::Blocked => __('Blocked'),
            self::Done => __('Done'),
        };
    }
}

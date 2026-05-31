<?php

namespace App\Enums;

enum LinkRelationshipType: string
{
    case RelatesTo = 'relates_to';
    case Blocks = 'blocks';
    case BlockedBy = 'blocked_by';
    case Duplicates = 'duplicates';
    case DuplicatedBy = 'duplicated_by';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::RelatesTo => __('Relates to'),
            self::Blocks => __('Blocks'),
            self::BlockedBy => __('Blocked by'),
            self::Duplicates => __('Duplicates'),
            self::DuplicatedBy => __('Duplicated by'),
        };
    }
}

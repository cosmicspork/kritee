<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Sent => __('Sent'),
            self::Viewed => __('Viewed'),
            self::Paid => __('Paid'),
            self::Overdue => __('Overdue'),
            self::Void => __('Void'),
        };
    }
}

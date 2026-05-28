<?php

namespace App\Enums;

enum AgentExecutionTriggerType: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case Webhook = 'webhook';
    case Queued = 'queued';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Manual'),
            self::Scheduled => __('Scheduled'),
            self::Webhook => __('Webhook'),
            self::Queued => __('Queued'),
        };
    }
}

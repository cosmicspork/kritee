<?php

namespace App\Enums;

enum AgentExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case TimedOut = 'timed_out';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Running => __('Running'),
            self::Completed => __('Completed'),
            self::Failed => __('Failed'),
            self::TimedOut => __('Timed out'),
        };
    }
}

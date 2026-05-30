<?php

namespace App\Models;

use App\Enums\ReviewQueueStatus;
use App\Enums\RiskLevel;
use Database\Factories\ReviewQueueItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ReviewQueueStatus $status
 * @property RiskLevel $risk_level
 */
#[Fillable([
    'agent_execution_id', 'action_type', 'action_payload', 'description',
    'status', 'risk_level', 'reviewed_by', 'reviewed_at', 'review_note',
    'expires_at', 'executed_at',
])]
class ReviewQueueItem extends Model
{
    /** @use HasFactory<ReviewQueueItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReviewQueueStatus::class,
            'risk_level' => RiskLevel::class,
            'action_payload' => 'array',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * The execution that queued this item for review.
     *
     * @return BelongsTo<AgentExecution, $this>
     */
    public function agentExecution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class);
    }

    /**
     * The user who reviewed this item, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

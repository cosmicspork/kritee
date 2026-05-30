<?php

namespace App\Models;

use App\Enums\AgentExecutionStatus;
use App\Enums\AgentExecutionTriggerType;
use Database\Factories\AgentExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AgentExecutionStatus $status
 * @property AgentExecutionTriggerType $trigger_type
 */
#[Fillable([
    'agent_name', 'triggered_by', 'trigger_type', 'status',
    'input_payload', 'output_payload', 'tool_calls',
    'iterations', 'max_iterations', 'tokens_input', 'tokens_output',
    'cost_estimate', 'duration_ms', 'error', 'started_at', 'completed_at',
])]
class AgentExecution extends Model
{
    /** @use HasFactory<AgentExecutionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_type' => AgentExecutionTriggerType::class,
            'status' => AgentExecutionStatus::class,
            'input_payload' => 'array',
            'output_payload' => 'array',
            'tool_calls' => 'array',
            'cost_estimate' => 'decimal:4',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The user who triggered this execution, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * The review queue items raised by this execution.
     *
     * @return HasMany<ReviewQueueItem, $this>
     */
    public function reviewQueueItems(): HasMany
    {
        return $this->hasMany(ReviewQueueItem::class);
    }
}

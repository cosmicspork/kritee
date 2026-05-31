<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property TicketStatus $status
 * @property TicketPriority $priority
 */
#[Fillable([
    'key', 'title', 'description', 'status', 'priority', 'is_blocked',
    'due_date', 'client_id', 'creator_id', 'assignee_id', 'sort_order',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * The human-readable key is generated as a fallback so a ticket created
     * outside the dedicated action still persists with a valid unique key.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (! $ticket->key) {
                $ticket->key = 'TK-'.(static::max('id') + 1);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'is_blocked' => 'boolean',
            'due_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The client this ticket belongs to, if any.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The user who created this ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * The user this ticket is assigned to, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * The projects this ticket is associated with.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'ticket_project')->withTimestamps();
    }

    /**
     * The comments left on this ticket.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The time entries logged against this ticket.
     *
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * The expenses incurred against this ticket.
     *
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * The files attached to this ticket.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * The cross-entity links anchored to this ticket.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkables(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }
}

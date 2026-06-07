<?php

namespace App\Models;

use App\Concerns\HasContentRef;
use App\Models\Contracts\ContentReferenced;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id', 'client_id', 'project_id', 'ticket_id', 'description', 'vendor',
    'amount', 'incurred_on', 'category', 'is_billable', 'is_billed', 'notes',
])]
class Expense extends Model implements ContentReferenced
{
    use HasContentRef;

    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * {@inheritDoc}
     */
    public function contentRefSource(): array
    {
        return [
            Carbon::parse($this->incurred_on)->format('Y-m-d'),
            (string) ($this->getAttribute('vendor') ?? ''),
            (string) ($this->project?->getAttribute('slug') ?? ''),
            number_format((float) $this->amount, 2, '.', ''),
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'incurred_on' => 'date',
            'is_billable' => 'boolean',
            'is_billed' => 'boolean',
        ];
    }

    /**
     * The user who incurred this expense.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The client this expense is attributed to, if any.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The project this expense is attributed to, if any.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The ticket this expense is attributed to, if any.
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Receipts attached to this expense.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Links between this expense and other records.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkables(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }
}

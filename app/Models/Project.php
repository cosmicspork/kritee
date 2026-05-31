<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * @property ProjectStatus $status
 */
#[Fillable([
    'client_id', 'name', 'slug', 'description', 'status',
    'budget', 'starts_at', 'ends_at',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            if (blank($project->slug)) {
                $project->slug = $project->generateUniqueSlug($project->name);
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
            'status' => ProjectStatus::class,
            'budget' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /**
     * The client this project belongs to; null for internal work.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Tickets associated with this project across the shared pivot.
     *
     * @return BelongsToMany<Ticket, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_project')->withTimestamps();
    }

    /**
     * Time logged against this project.
     *
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Expenses booked against this project.
     *
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Billing rates that resolve to this project in the rate cascade.
     *
     * @return MorphMany<BillingRate, $this>
     */
    public function billingRates(): MorphMany
    {
        return $this->morphMany(BillingRate::class, 'rateable');
    }

    /**
     * Links where this project is the source side.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkableSources(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }

    /**
     * Links where this project is the target side.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkableTargets(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'target');
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->whereKeyNot($this->getKey())->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

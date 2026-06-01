<?php

namespace App\Models;

use App\Enums\RoadmapStatus;
use Database\Factories\RoadmapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property RoadmapStatus $status
 */
#[Fillable(['title', 'description', 'status', 'client_id', 'is_public'])]
class Roadmap extends Model
{
    /** @use HasFactory<RoadmapFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoadmapStatus::class,
            'is_public' => 'boolean',
        ];
    }

    /**
     * Limit to roadmaps shared publicly.
     *
     * @param  Builder<Roadmap>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * Limit to active (non-archived) roadmaps.
     *
     * @param  Builder<Roadmap>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RoadmapStatus::Active);
    }

    /**
     * The client this roadmap belongs to, if any.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The items planned on this roadmap.
     *
     * @return HasMany<RoadmapItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RoadmapItem::class);
    }

    /**
     * Links between this roadmap and other records.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkables(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }
}

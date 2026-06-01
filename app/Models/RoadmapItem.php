<?php

namespace App\Models;

use App\Enums\RoadmapItemStatus;
use Database\Factories\RoadmapItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property RoadmapItemStatus $status
 */
#[Fillable([
    'roadmap_id', 'title', 'description', 'status',
    'starts_at', 'ends_at', 'sort_order', 'is_public',
])]
class RoadmapItem extends Model
{
    /** @use HasFactory<RoadmapItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoadmapItemStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Limit to items shared publicly.
     *
     * @param  Builder<RoadmapItem>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * The roadmap this item belongs to.
     *
     * @return BelongsTo<Roadmap, $this>
     */
    public function roadmap(): BelongsTo
    {
        return $this->belongsTo(Roadmap::class);
    }

    /**
     * Links where this item is the source.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkables(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }
}

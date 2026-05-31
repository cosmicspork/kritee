<?php

namespace App\Models;

use App\Enums\LinkRelationshipType;
use Database\Factories\LinkableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property LinkRelationshipType $relationship_type
 */
#[Fillable([
    'source_type', 'source_id', 'target_type', 'target_id',
    'relationship_type', 'note',
])]
class Linkable extends Model
{
    /** @use HasFactory<LinkableFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship_type' => LinkRelationshipType::class,
        ];
    }

    /**
     * The record the link originates from.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The record the link points to.
     *
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}

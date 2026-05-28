<?php

namespace App\Models;

use Database\Factories\BillingRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $amount
 */
#[Fillable([
    'rateable_type', 'rateable_id', 'amount',
    'effective_from', 'effective_to', 'label',
])]
class BillingRate extends Model
{
    /** @use HasFactory<BillingRateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * The entity this rate applies to (user, client, project, or ticket).
     *
     * @return MorphTo<Model, $this>
     */
    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }
}

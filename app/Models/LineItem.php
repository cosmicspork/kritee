<?php

namespace App\Models;

use Database\Factories\LineItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'invoice_id', 'description', 'quantity', 'unit_price', 'amount', 'sort_order',
])]
class LineItem extends Model
{
    /** @use HasFactory<LineItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The invoice this line item belongs to.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Links originating from this line item.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkables(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }
}

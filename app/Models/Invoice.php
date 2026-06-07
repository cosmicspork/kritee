<?php

namespace App\Models;

use App\Concerns\HasContentRef;
use App\Enums\InvoiceStatus;
use App\Models\Contracts\ContentReferenced;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property InvoiceStatus $status
 */
#[Fillable([
    'invoice_number', 'client_id', 'status', 'issued_at', 'due_at', 'paid_at',
    'subtotal', 'tax_rate', 'tax_amount', 'total', 'notes', 'terms',
])]
class Invoice extends Model implements ContentReferenced
{
    use HasContentRef;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * {@inheritDoc}
     *
     * `invoice_number` already uniquely identifies an invoice; the `ref` is the
     * uniform content key the shared trait gives every referenced model.
     */
    public function contentRefSource(): array
    {
        return [
            $this->issued_at !== null ? Carbon::parse($this->issued_at)->format('Y-m-d') : '',
            (string) ($this->client?->getAttribute('slug') ?? ''),
            number_format((float) $this->total, 2, '.', ''),
            (string) ($this->getAttribute('invoice_number') ?? ''),
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
            'status' => InvoiceStatus::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * The client this invoice is billed to.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The line items that make up this invoice.
     *
     * @return HasMany<LineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }

    /**
     * Links where this invoice is the source.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkSources(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'source');
    }

    /**
     * Links where this invoice is the target.
     *
     * @return MorphMany<Linkable, $this>
     */
    public function linkTargets(): MorphMany
    {
        return $this->morphMany(Linkable::class, 'target');
    }
}

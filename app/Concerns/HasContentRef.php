<?php

namespace App\Concerns;

use App\Models\Contracts\ContentReferenced;
use App\Services\Support\ContentRef;
use Illuminate\Database\Eloquent\Model;

/**
 * Fills a model's `ref` from its {@see ContentReferenced::contentRefSource()} the
 * first time it is saved, so both imported and web-entered rows carry the same
 * dedup key. Set once at creation: a later edit does not rewrite `ref` (a content
 * hash and its row would otherwise drift apart).
 *
 * @mixin Model
 */
trait HasContentRef
{
    public static function bootHasContentRef(): void
    {
        static::saving(function (Model $model): void {
            if (! $model instanceof ContentReferenced) {
                return;
            }

            if (blank($model->getAttribute('ref'))) {
                $model->setAttribute('ref', app(ContentRef::class)->compute($model->contentRefSource()));
            }
        });
    }
}

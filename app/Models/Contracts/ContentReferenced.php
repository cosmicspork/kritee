<?php

namespace App\Models\Contracts;

/**
 * A model that carries a content-hash `ref`. Implementers declare which field
 * values feed the hash; the HasContentRef trait fills `ref` on save.
 */
interface ContentReferenced
{
    /**
     * The canonical field values, in fixed order, that the `ref` hashes.
     *
     * @return list<string>
     */
    public function contentRefSource(): array;
}

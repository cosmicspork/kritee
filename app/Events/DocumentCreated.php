<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new document was persisted. Carries the document and the actor id behind the
 * write so downstream listeners (and the future agent audit log) can attribute
 * it without a follow-up query.
 */
final class DocumentCreated implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Document $document,
        public readonly ?string $actorId = null,
    ) {}
}

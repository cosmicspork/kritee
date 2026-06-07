<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Documents are visible to every authenticated user, but only the uploader (or
 * an admin) may change or remove one.
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    private function owns(User $user, Document $document): bool
    {
        return $user->isAdmin() || (int) $document->uploaded_by === (int) $user->getKey();
    }
}

<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

/**
 * Expenses are owned by the user who incurred them. Admins may act on any
 * expense; members are scoped to their own.
 */
class ExpensePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->owns($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->owns($user, $expense);
    }

    private function owns(User $user, Expense $expense): bool
    {
        return $user->isAdmin() || (int) $expense->user_id === (int) $user->getKey();
    }
}

<?php

namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_activity::log');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->can('view_activity::log');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Activity logs are created automatically, not manually
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        // Activity logs should be immutable
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        // Activity logs should be immutable for audit trail integrity
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Activity logs should be immutable for audit trail integrity
        return false;
    }
}

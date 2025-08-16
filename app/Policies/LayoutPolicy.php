<?php

namespace App\Policies;

use App\Models\Layout;
use App\Models\User;

class LayoutPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_layout');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_layout');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Layout $layout): bool
    {
        return $user->can('update_layout');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Layout $layout): bool
    {
        return $user->can('delete_layout');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_layout');
    }
}

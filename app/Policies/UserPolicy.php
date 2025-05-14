<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return auth()->user()->can('view_user');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return auth()->user()->can('create_user');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return auth()->user()->can('update_user');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return auth()->user()->can('delete_user');
    }

    public function deleteAny(User $user): bool
    {
        return auth()->user()->can('delete_any_user');
    }
}

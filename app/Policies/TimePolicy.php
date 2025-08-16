<?php

namespace App\Policies;

use App\Models\Time;
use App\Models\User;

class TimePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return auth()->user()->can('view_time');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_time');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Time $time): bool
    {
        return $user->can('update_time');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Time $time): bool
    {
        return $user->can('delete_time');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_time');
    }
}

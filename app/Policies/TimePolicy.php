<?php

namespace App\Policies;

use App\Models\Time;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        return auth()->user()->can('create_time');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Time $time): bool
    {
        return auth()->user()->can('update_time');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Time $time): bool
    {
        return auth()->user()->can('delete_time');
    }

    public function deleteAny(User $user): bool
    {
        return auth()->user()->can('delete_any_time');
    }
}

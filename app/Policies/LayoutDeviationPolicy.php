<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LayoutDeviation;
use Illuminate\Auth\Access\HandlesAuthorization;

class LayoutDeviationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_layout::deviation');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_layout::deviation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LayoutDeviation $layoutDeviation): bool
    {
        return $user->can('update_layout::deviation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LayoutDeviation $layoutDeviation): bool
    {
        return $user->can('delete_layout::deviation');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_layout::deviation');
    }
}

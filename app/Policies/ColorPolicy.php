<?php

namespace App\Policies;

use App\Models\Color;
use App\Models\User;

class ColorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return auth()->user()->can('view_color');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return auth()->user()->can('create_color');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Color $color): bool
    {
        return auth()->user()->can('update_color');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Color $color): bool
    {
        return auth()->user()->can('delete_color');
    }

    public function deleteAny(User $user): bool
    {
        return auth()->user()->can('delete_any_color');
    }
}

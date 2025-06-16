<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Absence;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbsencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return ($user->can('view_any_absence'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Absence $absence): bool
    {
        if ($user->can('view_absence')) {
            if ($absence->user_id === $user->id or $user->can('view_any_absence')) return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_absence');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Absence $absence): bool
    {
        if ($user->can('update_absence')) {
            if ($absence->user_id === $user->id or $user->can('view_any_absence')) return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Absence $absence): bool
    {
        if ($user->can('delete_absence')) {
            if ($absence->user_id === $user->id or $user->can('view_any_absence')) return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_absence');
    }
}

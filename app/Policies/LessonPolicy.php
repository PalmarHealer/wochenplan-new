<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return ($user->can('view_any_lesson'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->can('view_lesson')) {
            if ($lesson->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return auth()->user()->can('create_lesson');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        if ($user->can('update_lesson')) {
            if ($lesson->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        if ($user->can('delete_lesson')) {
            if ($lesson->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson')) {
                return true;
            }
        }

        return false;
    }

    public function deleteAny(User $user): bool
    {
        return auth()->user()->can('delete_any_lesson');
    }
}

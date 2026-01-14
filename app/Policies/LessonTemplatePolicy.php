<?php

namespace App\Policies;

use App\Models\LessonTemplate;
use App\Models\User;

class LessonTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_lesson::template') or $user->can('view_lesson::template');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LessonTemplate $lessonTemplate): bool
    {
        if ($user->can('view_lesson::template')) {
            if ($lessonTemplate->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson::template')) {
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
        return $user->can('create_lesson::template');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LessonTemplate $lessonTemplate): bool
    {
        if ($user->can('update_lesson::template')) {
            if ($lessonTemplate->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson::template')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LessonTemplate $lessonTemplate): bool
    {
        if ($user->can('delete_lesson::template')) {
            if ($lessonTemplate->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson::template')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, LessonTemplate $lessonTemplate): bool
    {
        if ($user->can('force_delete_lesson::template')) {
            if ($lessonTemplate->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson::template')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_lesson::template');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, LessonTemplate $lessonTemplate): bool
    {
        if ($user->can('restore_lesson::template')) {
            if ($lessonTemplate->assignedUsers()->where('user_id', $user->id)->exists() || $user->can('view_any_lesson::template')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_lesson::template');
    }
}

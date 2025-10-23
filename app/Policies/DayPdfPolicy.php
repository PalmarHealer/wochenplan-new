<?php

namespace App\Policies;

use App\Models\DayPdf;
use App\Models\User;

class DayPdfPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_day::pdf');
    }

    public function view(User $user, DayPdf $dayPdf): bool
    {
        // Same as viewAny - no differentiation needed
        return $user->can('view_day::pdf');
    }

    public function create(User $user): bool
    {
        return $user->can('create_day::pdf');
    }

    public function delete(User $user, DayPdf $dayPdf): bool
    {
        return $user->can('delete_day::pdf');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_day::pdf');
    }
}

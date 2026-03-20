<?php

namespace App\Policies;

use App\Models\User;

class ApiAccessPolicy
{
    public function useApi(User $user): bool
    {
        return $user->hasPermissionTo('api.access');
    }
}

<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Spatie\Permission\Models\Role;

class ListRoles implements AiChatTool
{
    public function name(): string
    {
        return 'list_roles';
    }

    public function displayName(): string
    {
        return 'Rollen auflisten';
    }

    public function description(): string
    {
        return 'List all roles with their name and number of permissions.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_role';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $roles = Role::withCount('permissions')->get();

        return [
            'count' => $roles->count(),
            'roles' => $roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
            ])->toArray(),
        ];
    }
}

<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Spatie\Permission\Models\Role;

class DeleteRole implements AiChatTool
{
    public function name(): string
    {
        return 'delete_role';
    }

    public function displayName(): string
    {
        return 'Rolle löschen';
    }

    public function description(): string
    {
        return 'Delete a role by its ID. The super_admin role cannot be deleted.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'role_id' => [
                    'type' => 'integer',
                    'description' => 'The role ID to delete (required)',
                ],
            ],
            'required' => ['role_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_role';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $role = Role::find($arguments['role_id']);

        if (! $role) {
            return ['error' => 'Rolle nicht gefunden.'];
        }

        if ($role->name === 'super_admin') {
            return ['error' => 'Die Rolle "super_admin" kann nicht gelöscht werden.'];
        }

        $name = $role->name;
        $role->delete();

        return [
            'success' => true,
            'message' => "Rolle \"{$name}\" wurde gelöscht.",
        ];
    }
}

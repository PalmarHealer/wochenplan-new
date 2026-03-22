<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Spatie\Permission\Models\Role;

class UpdateRole implements AiChatTool
{
    public function name(): string
    {
        return 'update_role';
    }

    public function displayName(): string
    {
        return 'Rolle bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing role by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'role_id' => [
                    'type' => 'integer',
                    'description' => 'The role ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New role name',
                ],
            ],
            'required' => ['role_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_role';
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

        if (array_key_exists('name', $arguments)) {
            $role->update(['name' => $arguments['name']]);
        }

        return [
            'success' => true,
            'message' => 'Rolle erfolgreich aktualisiert.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
        ];
    }
}

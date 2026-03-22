<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Spatie\Permission\Models\Role;

class CreateRole implements AiChatTool
{
    public function name(): string
    {
        return 'create_role';
    }

    public function displayName(): string
    {
        return 'Rolle erstellen';
    }

    public function description(): string
    {
        return 'Create a new role.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Role name (required)',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_role';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $role = Role::create([
            'name' => $arguments['name'],
            'guard_name' => 'web',
        ]);

        return [
            'success' => true,
            'message' => "Rolle \"{$role->name}\" erfolgreich erstellt.",
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
        ];
    }
}

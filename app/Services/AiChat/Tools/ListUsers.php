<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListUsers implements AiChatTool
{
    public function name(): string
    {
        return 'list_users';
    }

    public function displayName(): string
    {
        return 'Benutzer auflisten';
    }

    public function description(): string
    {
        return 'List all users with their display name, email, and roles.';
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
        return 'view_user';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $users = User::with('roles')->get();

        return [
            'count' => $users->count(),
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'display_name' => $u->display_name ?? $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name')->toArray(),
            ])->toArray(),
        ];
    }
}

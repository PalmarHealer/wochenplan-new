<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Illuminate\Support\Facades\Hash;

class CreateUser implements AiChatTool
{
    public function name(): string
    {
        return 'create_user';
    }

    public function displayName(): string
    {
        return 'Benutzer erstellen';
    }

    public function description(): string
    {
        return 'Create a new user account with name, email, and password.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Username (required)',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Email address (required)',
                ],
                'password' => [
                    'type' => 'string',
                    'description' => 'Password (required)',
                ],
                'display_name' => [
                    'type' => 'string',
                    'description' => 'Display name',
                ],
            ],
            'required' => ['name', 'email', 'password'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_user';
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
        $newUser = User::create([
            'name' => $arguments['name'],
            'email' => $arguments['email'],
            'password' => Hash::make($arguments['password']),
            'display_name' => $arguments['display_name'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Benutzer erfolgreich erstellt.',
            'user' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'display_name' => $newUser->display_name ?? $newUser->name,
                'email' => $newUser->email,
            ],
        ];
    }
}

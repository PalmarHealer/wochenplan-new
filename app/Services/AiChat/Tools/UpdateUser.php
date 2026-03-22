<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Illuminate\Support\Facades\Hash;

class UpdateUser implements AiChatTool
{
    public function name(): string
    {
        return 'update_user';
    }

    public function displayName(): string
    {
        return 'Benutzer bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing user by their ID. Only provided fields will be changed.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'The user ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New username',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'New email address',
                ],
                'display_name' => [
                    'type' => 'string',
                    'description' => 'New display name',
                ],
                'password' => [
                    'type' => 'string',
                    'description' => 'New password (will be hashed)',
                ],
            ],
            'required' => ['user_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_user';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $targetUser = User::find($arguments['user_id']);

        if (! $targetUser) {
            return ['error' => 'Benutzer nicht gefunden.'];
        }

        $updateData = [];

        if (array_key_exists('name', $arguments)) {
            $updateData['name'] = $arguments['name'];
        }

        if (array_key_exists('email', $arguments)) {
            $updateData['email'] = $arguments['email'];
        }

        if (array_key_exists('display_name', $arguments)) {
            $updateData['display_name'] = $arguments['display_name'];
        }

        if (array_key_exists('password', $arguments)) {
            $updateData['password'] = Hash::make($arguments['password']);
        }

        $targetUser->update($updateData);

        return [
            'success' => true,
            'message' => 'Benutzer erfolgreich aktualisiert.',
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'display_name' => $targetUser->display_name ?? $targetUser->name,
                'email' => $targetUser->email,
            ],
        ];
    }
}

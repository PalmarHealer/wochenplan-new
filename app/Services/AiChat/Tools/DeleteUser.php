<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteUser implements AiChatTool
{
    public function name(): string
    {
        return 'delete_user';
    }

    public function displayName(): string
    {
        return 'Benutzer löschen';
    }

    public function description(): string
    {
        return 'Delete a user by their ID. Cannot delete yourself.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'The user ID to delete (required)',
                ],
            ],
            'required' => ['user_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_user';
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
        if ($user->id === $arguments['user_id']) {
            return ['error' => 'Du kannst dich nicht selbst löschen.'];
        }

        $targetUser = User::find($arguments['user_id']);

        if (! $targetUser) {
            return ['error' => 'Benutzer nicht gefunden.'];
        }

        $displayName = $targetUser->display_name ?? $targetUser->name;
        $targetUser->delete();

        return [
            'success' => true,
            'message' => "Benutzer \"{$displayName}\" wurde gelöscht.",
        ];
    }
}

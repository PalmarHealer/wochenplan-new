<?php

namespace App\Services\AiChat\Tools;

use App\Models\Layout;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteLayout implements AiChatTool
{
    public function name(): string
    {
        return 'delete_layout';
    }

    public function displayName(): string
    {
        return 'Layout löschen';
    }

    public function description(): string
    {
        return 'Delete a layout by its ID. This action is permanent.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'layout_id' => [
                    'type' => 'integer',
                    'description' => 'The layout ID to delete (required)',
                ],
            ],
            'required' => ['layout_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_layout';
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
        $layout = Layout::find($arguments['layout_id']);

        if (! $layout) {
            return ['error' => 'Layout nicht gefunden.'];
        }

        $name = $layout->name;
        $layout->delete();

        return [
            'success' => true,
            'message' => "Layout \"{$name}\" wurde gelöscht.",
        ];
    }
}

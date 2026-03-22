<?php

namespace App\Services\AiChat\Tools;

use App\Models\Color;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteColor implements AiChatTool
{
    public function name(): string
    {
        return 'delete_color';
    }

    public function displayName(): string
    {
        return 'Farbe löschen';
    }

    public function description(): string
    {
        return 'Delete a color by its ID. This action is permanent.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'The color ID to delete (required)',
                ],
            ],
            'required' => ['color_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_color';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $color = Color::find($arguments['color_id']);

        if (! $color) {
            return ['error' => 'Farbe nicht gefunden.'];
        }

        $name = $color->name;
        $color->delete();

        return [
            'success' => true,
            'message' => "Farbe \"{$name}\" wurde gelöscht.",
        ];
    }
}

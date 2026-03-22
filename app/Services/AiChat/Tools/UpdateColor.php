<?php

namespace App\Services\AiChat\Tools;

use App\Models\Color;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateColor implements AiChatTool
{
    public function name(): string
    {
        return 'update_color';
    }

    public function displayName(): string
    {
        return 'Farbe bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing color by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'The color ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New color name',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'New hex color code, e.g. #FF0000',
                ],
            ],
            'required' => ['color_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_color';
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

        $updateData = [];

        if (array_key_exists('name', $arguments)) {
            $updateData['name'] = $arguments['name'];
        }

        if (array_key_exists('color', $arguments)) {
            $updateData['color'] = $arguments['color'];
        }

        $color->update($updateData);

        return [
            'success' => true,
            'message' => "Farbe \"{$color->name}\" wurde aktualisiert.",
        ];
    }
}

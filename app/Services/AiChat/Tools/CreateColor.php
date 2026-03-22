<?php

namespace App\Services\AiChat\Tools;

use App\Models\Color;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateColor implements AiChatTool
{
    public function name(): string
    {
        return 'create_color';
    }

    public function displayName(): string
    {
        return 'Farbe erstellen';
    }

    public function description(): string
    {
        return 'Create a new color with a name and hex color code.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Color name (required)',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Hex color code, e.g. #FF0000 (required)',
                ],
            ],
            'required' => ['name', 'color'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_color';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $color = Color::create([
            'name' => $arguments['name'],
            'color' => $arguments['color'],
        ]);

        return [
            'success' => true,
            'message' => "Farbe \"{$color->name}\" wurde erstellt.",
        ];
    }
}

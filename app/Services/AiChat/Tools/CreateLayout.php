<?php

namespace App\Services\AiChat\Tools;

use App\Models\Layout;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateLayout implements AiChatTool
{
    private const WEEKDAY_NAMES = [
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
    ];

    public function name(): string
    {
        return 'create_layout';
    }

    public function displayName(): string
    {
        return 'Layout erstellen';
    }

    public function description(): string
    {
        return 'Create a new layout. The layout grid data cannot be set via AI and defaults to empty. Weekdays: 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Layout name (required)',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Layout description',
                ],
                'weekdays' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array of weekday numbers (1-5)',
                ],
                'text_size' => [
                    'type' => 'number',
                    'description' => 'Text size percentage (default: 100)',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_layout';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $layout = Layout::create([
            'name' => $arguments['name'],
            'description' => $arguments['description'] ?? null,
            'weekdays' => $arguments['weekdays'] ?? null,
            'text_size' => $arguments['text_size'] ?? 100,
            'layout' => '[]',
        ]);

        return [
            'success' => true,
            'message' => 'Layout erfolgreich erstellt.',
            'layout' => [
                'id' => $layout->id,
                'name' => $layout->name,
                'description' => $layout->description,
                'weekdays' => is_array($layout->weekdays)
                    ? collect($layout->weekdays)
                        ->sort()
                        ->map(fn ($d) => self::WEEKDAY_NAMES[(int) $d] ?? $d)
                        ->values()
                        ->toArray()
                    : [],
                'text_size' => $layout->text_size,
            ],
        ];
    }
}

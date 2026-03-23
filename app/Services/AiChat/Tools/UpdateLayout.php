<?php

namespace App\Services\AiChat\Tools;

use App\Models\Layout;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateLayout implements AiChatTool
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
        return 'update_layout';
    }

    public function displayName(): string
    {
        return 'Layout bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing layout by its ID. Only provided fields will be changed. The layout grid data cannot be edited via AI.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'layout_id' => [
                    'type' => 'integer',
                    'description' => 'The layout ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New layout name',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'New description',
                ],
                'weekdays' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'New array of weekday numbers (1-5)',
                ],
                'text_size' => [
                    'type' => 'number',
                    'description' => 'New text size percentage',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'New notes',
                ],
            ],
            'required' => ['layout_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_layout';
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

        $updateData = [];

        $fieldMap = [
            'name' => 'name',
            'description' => 'description',
            'weekdays' => 'weekdays',
            'text_size' => 'text_size',
            'notes' => 'notes',
        ];

        foreach ($fieldMap as $argKey => $dbKey) {
            if (array_key_exists($argKey, $arguments)) {
                $updateData[$dbKey] = $arguments[$argKey];
            }
        }

        $layout->update($updateData);
        $layout->refresh();

        return [
            'success' => true,
            'message' => 'Layout erfolgreich aktualisiert.',
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

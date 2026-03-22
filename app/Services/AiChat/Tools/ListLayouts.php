<?php

namespace App\Services\AiChat\Tools;

use App\Models\Layout;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListLayouts implements AiChatTool
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
        return 'list_layouts';
    }

    public function displayName(): string
    {
        return 'Layouts auflisten';
    }

    public function description(): string
    {
        return 'List all available layouts with their name, description, and assigned weekdays.';
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
        return 'view_layout';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $layouts = Layout::all();

        return [
            'count' => $layouts->count(),
            'layouts' => $layouts->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'description' => $l->description,
                'weekdays' => is_array($l->weekdays)
                    ? collect($l->weekdays)
                        ->sort()
                        ->map(fn ($d) => self::WEEKDAY_NAMES[(int) $d] ?? $d)
                        ->values()
                        ->toArray()
                    : [],
            ])->toArray(),
        ];
    }
}

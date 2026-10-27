<?php

namespace App\Services\AiChat\Tools;

use App\Models\Color;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListColors implements AiChatTool
{
    public function name(): string
    {
        return 'list_colors';
    }

    public function displayName(): string
    {
        return 'Farben auflisten';
    }

    public function description(): string
    {
        return 'List all available colors (Farben). Returns color IDs, names, and hex values. Use color IDs when creating or updating lessons.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $colors = Color::all();

        return [
            'count' => $colors->count(),
            'colors' => $colors->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'hex' => $c->color])->toArray(),
            'summary' => $colors->map(fn ($c) => "{$c->name} ({$c->color})")->implode(', '),
        ];
    }
}

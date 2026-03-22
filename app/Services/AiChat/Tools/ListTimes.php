<?php

namespace App\Services\AiChat\Tools;

use App\Models\Time;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListTimes implements AiChatTool
{
    public function name(): string
    {
        return 'list_times';
    }

    public function displayName(): string
    {
        return 'Zeiten auflisten';
    }

    public function description(): string
    {
        return 'List all available time slots (Zeiten). Returns time slot IDs and names. Use time IDs when creating or updating lessons.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $times = Time::all();

        return [
            'count' => $times->count(),
            'times' => $times->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->toArray(),
            'summary' => $times->pluck('name')->implode(', '),
        ];
    }
}

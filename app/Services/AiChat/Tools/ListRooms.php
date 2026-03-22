<?php

namespace App\Services\AiChat\Tools;

use App\Models\Room;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListRooms implements AiChatTool
{
    public function name(): string
    {
        return 'list_rooms';
    }

    public function displayName(): string
    {
        return 'Räume auflisten';
    }

    public function description(): string
    {
        return 'List all available rooms (Räume). Returns room IDs and names. Use room IDs when creating or updating lessons.';
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
        $rooms = Room::all();

        return [
            'count' => $rooms->count(),
            'rooms' => $rooms->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->toArray(),
            'summary' => $rooms->pluck('name')->implode(', '),
        ];
    }
}

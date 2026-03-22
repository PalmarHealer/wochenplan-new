<?php

namespace App\Services\AiChat\Tools;

use App\Models\Room;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateRoom implements AiChatTool
{
    public function name(): string
    {
        return 'create_room';
    }

    public function displayName(): string
    {
        return 'Raum erstellen';
    }

    public function description(): string
    {
        return 'Create a new room.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Room name (required)',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_room';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $room = Room::create(['name' => $arguments['name']]);

        return [
            'success' => true,
            'message' => "Raum \"{$room->name}\" wurde erstellt.",
        ];
    }
}

<?php

namespace App\Services\AiChat\Tools;

use App\Models\Room;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteRoom implements AiChatTool
{
    public function name(): string
    {
        return 'delete_room';
    }

    public function displayName(): string
    {
        return 'Raum löschen';
    }

    public function description(): string
    {
        return 'Delete a room by its ID. This action is permanent.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'room_id' => [
                    'type' => 'integer',
                    'description' => 'The room ID to delete (required)',
                ],
            ],
            'required' => ['room_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_room';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $room = Room::find($arguments['room_id']);

        if (! $room) {
            return ['error' => 'Raum nicht gefunden.'];
        }

        $name = $room->name;
        $room->delete();

        return [
            'success' => true,
            'message' => "Raum \"{$name}\" wurde gelöscht.",
        ];
    }
}

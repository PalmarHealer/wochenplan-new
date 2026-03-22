<?php

namespace App\Services\AiChat\Tools;

use App\Models\Room;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateRoom implements AiChatTool
{
    public function name(): string
    {
        return 'update_room';
    }

    public function displayName(): string
    {
        return 'Raum bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing room by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'room_id' => [
                    'type' => 'integer',
                    'description' => 'The room ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New room name',
                ],
            ],
            'required' => ['room_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_room';
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

        $updateData = [];

        if (array_key_exists('name', $arguments)) {
            $updateData['name'] = $arguments['name'];
        }

        $room->update($updateData);

        return [
            'success' => true,
            'message' => "Raum \"{$room->name}\" wurde aktualisiert.",
        ];
    }
}

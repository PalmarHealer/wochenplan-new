<?php

namespace App\Services\AiChat\Tools;

use App\Models\Time;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteTime implements AiChatTool
{
    public function name(): string
    {
        return 'delete_time';
    }

    public function displayName(): string
    {
        return 'Zeit löschen';
    }

    public function description(): string
    {
        return 'Delete a time slot by its ID. This action is permanent.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'time_id' => [
                    'type' => 'integer',
                    'description' => 'The time slot ID to delete (required)',
                ],
            ],
            'required' => ['time_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_time';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $time = Time::find($arguments['time_id']);

        if (! $time) {
            return ['error' => 'Zeit nicht gefunden.'];
        }

        $name = $time->name;
        $time->delete();

        return [
            'success' => true,
            'message' => "Zeit \"{$name}\" wurde gelöscht.",
        ];
    }
}

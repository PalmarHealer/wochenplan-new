<?php

namespace App\Services\AiChat\Tools;

use App\Models\Time;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateTime implements AiChatTool
{
    public function name(): string
    {
        return 'update_time';
    }

    public function displayName(): string
    {
        return 'Zeit bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing time slot by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'time_id' => [
                    'type' => 'integer',
                    'description' => 'The time slot ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New time slot name',
                ],
            ],
            'required' => ['time_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_time';
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

        $updateData = [];

        if (array_key_exists('name', $arguments)) {
            $updateData['name'] = $arguments['name'];
        }

        $time->update($updateData);

        return [
            'success' => true,
            'message' => "Zeit \"{$time->name}\" wurde aktualisiert.",
        ];
    }
}

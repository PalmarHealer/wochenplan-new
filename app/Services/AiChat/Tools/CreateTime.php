<?php

namespace App\Services\AiChat\Tools;

use App\Models\Time;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateTime implements AiChatTool
{
    public function name(): string
    {
        return 'create_time';
    }

    public function displayName(): string
    {
        return 'Zeit erstellen';
    }

    public function description(): string
    {
        return 'Create a new time slot.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Time slot name (required)',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_time';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $time = Time::create(['name' => $arguments['name']]);

        return [
            'success' => true,
            'message' => "Zeit \"{$time->name}\" wurde erstellt.",
        ];
    }
}

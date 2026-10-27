<?php

namespace App\Services\AiChat\Tools;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateLesson implements AiChatTool
{
    public function name(): string
    {
        return 'create_lesson';
    }

    public function displayName(): string
    {
        return 'Angebot erstellen';
    }

    public function description(): string
    {
        return 'Create a new lesson (Angebot) for a specific date. Requires at minimum a name and date. You must use valid room_id, time_id, and color_id values based on the available rooms, time slots, and colors.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Lesson name (required)',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Lesson description',
                ],
                'date' => [
                    'type' => 'string',
                    'description' => 'Lesson date (YYYY-MM-DD format, required)',
                ],
                'room_id' => [
                    'type' => 'integer',
                    'description' => 'Room ID',
                ],
                'time_id' => [
                    'type' => 'integer',
                    'description' => 'Time slot ID',
                ],
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'Color ID',
                ],
                'assigned_user_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array of user IDs to assign',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Additional notes',
                ],
                'disabled' => [
                    'type' => 'boolean',
                    'description' => 'Whether the lesson is disabled (default: false)',
                ],
            ],
            'required' => ['name', 'date'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_lesson';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $lesson = Lesson::create([
            'name' => $arguments['name'],
            'description' => $arguments['description'] ?? null,
            'date' => $arguments['date'],
            'room' => $arguments['room_id'] ?? null,
            'lesson_time' => $arguments['time_id'] ?? null,
            'color' => $arguments['color_id'] ?? null,
            'notes' => $arguments['notes'] ?? null,
            'disabled' => $arguments['disabled'] ?? false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($arguments['assigned_user_ids'])) {
            $lesson->assignedUsers()->sync($arguments['assigned_user_ids']);
        }

        $lesson->load(['rooms', 'times', 'colors', 'assignedUsers']);

        return [
            'success' => true,
            'message' => 'Angebot erfolgreich erstellt.',
            'lesson' => [
                'id' => $lesson->id,
                'name' => strip_tags($lesson->name),
                'date' => $lesson->date,
                'room' => $lesson->rooms?->name,
                'time' => $lesson->times?->name,
                'color' => $lesson->colors?->name,
                'assigned_users' => $lesson->assignedUsers->pluck('display_name', 'id')->toArray(),
            ],
        ];
    }
}

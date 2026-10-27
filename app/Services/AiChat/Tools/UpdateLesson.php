<?php

namespace App\Services\AiChat\Tools;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateLesson implements AiChatTool
{
    public function name(): string
    {
        return 'update_lesson';
    }

    public function displayName(): string
    {
        return 'Angebot bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing lesson (Angebot) by its ID. Only provided fields will be changed.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lesson_id' => [
                    'type' => 'integer',
                    'description' => 'The lesson ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New lesson name',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'New description',
                ],
                'date' => [
                    'type' => 'string',
                    'description' => 'New date (YYYY-MM-DD)',
                ],
                'room_id' => [
                    'type' => 'integer',
                    'description' => 'New room ID',
                ],
                'time_id' => [
                    'type' => 'integer',
                    'description' => 'New time slot ID',
                ],
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'New color ID',
                ],
                'assigned_user_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'New array of assigned user IDs (replaces existing)',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'New notes',
                ],
                'disabled' => [
                    'type' => 'boolean',
                    'description' => 'Whether the lesson is disabled',
                ],
            ],
            'required' => ['lesson_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_lesson';
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
        $lesson = Lesson::find($arguments['lesson_id']);

        if (! $lesson) {
            return ['error' => 'Angebot nicht gefunden.'];
        }

        // Policy check: user must be assigned or have view_any_lesson
        if (! $user->can('view_any_lesson') && ! $lesson->assignedUsers()->where('user_id', $user->id)->exists()) {
            return ['error' => 'Keine Berechtigung für dieses Angebot.'];
        }

        $updateData = ['updated_by' => $user->id];

        $fieldMap = [
            'name' => 'name',
            'description' => 'description',
            'date' => 'date',
            'room_id' => 'room',
            'time_id' => 'lesson_time',
            'color_id' => 'color',
            'notes' => 'notes',
            'disabled' => 'disabled',
        ];

        foreach ($fieldMap as $argKey => $dbKey) {
            if (array_key_exists($argKey, $arguments)) {
                $updateData[$dbKey] = $arguments[$argKey];
            }
        }

        $lesson->update($updateData);

        if (array_key_exists('assigned_user_ids', $arguments)) {
            $lesson->assignedUsers()->sync($arguments['assigned_user_ids']);
        }

        $lesson->load(['rooms', 'times', 'colors', 'assignedUsers']);

        return [
            'success' => true,
            'message' => 'Angebot erfolgreich aktualisiert.',
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

<?php

namespace App\Services\AiChat\Tools;

use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateLessonTemplate implements AiChatTool
{
    public function name(): string
    {
        return 'create_lesson_template';
    }

    public function displayName(): string
    {
        return 'Angebotsvorlage erstellen';
    }

    public function description(): string
    {
        return 'Create a new lesson template (Angebotsvorlage) for recurring lessons. Weekdays: 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Template name (required)',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Template description',
                ],
                'weekday' => [
                    'type' => 'integer',
                    'description' => 'Weekday number 1-5 (required, 1=Monday to 5=Friday)',
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
                    'description' => 'Whether the template is disabled (default: false)',
                ],
            ],
            'required' => ['name', 'weekday'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_lesson::template';
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
        $template = LessonTemplate::create([
            'name' => $arguments['name'],
            'description' => $arguments['description'] ?? null,
            'weekday' => $arguments['weekday'],
            'room' => $arguments['room_id'] ?? null,
            'lesson_time' => $arguments['time_id'] ?? null,
            'color' => $arguments['color_id'] ?? null,
            'notes' => $arguments['notes'] ?? null,
            'disabled' => $arguments['disabled'] ?? false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($arguments['assigned_user_ids'])) {
            $template->assignedUsers()->sync($arguments['assigned_user_ids']);
        }

        $template->load(['rooms', 'times', 'colors', 'assignedUsers']);

        $weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

        return [
            'success' => true,
            'message' => 'Angebotsvorlage erfolgreich erstellt.',
            'template' => [
                'id' => $template->id,
                'name' => strip_tags($template->name),
                'weekday' => $template->weekday,
                'weekday_name' => $weekdayNames[$template->weekday] ?? $template->weekday,
                'room' => $template->rooms?->name,
                'time' => $template->times?->name,
                'color' => $template->colors?->name,
                'assigned_users' => $template->assignedUsers->pluck('display_name', 'id')->toArray(),
            ],
        ];
    }
}

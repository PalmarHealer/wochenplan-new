<?php

namespace App\Services\AiChat\Tools;

use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateLessonTemplate implements AiChatTool
{
    public function name(): string
    {
        return 'update_lesson_template';
    }

    public function displayName(): string
    {
        return 'Angebotsvorlage bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing lesson template (Angebotsvorlage) by its ID. Only provided fields will be changed.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'The template ID to update (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New template name',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'New description',
                ],
                'weekday' => [
                    'type' => 'integer',
                    'description' => 'New weekday (1=Monday to 5=Friday)',
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
                    'description' => 'Whether the template is disabled',
                ],
            ],
            'required' => ['template_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_lesson::template';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $template = LessonTemplate::find($arguments['template_id']);

        if (! $template) {
            return ['error' => 'Angebotsvorlage nicht gefunden.'];
        }

        // Policy check: user must be assigned or have view_any_lesson::template
        if (! $user->can('view_any_lesson::template') && ! $template->assignedUsers()->where('user_id', $user->id)->exists()) {
            return ['error' => 'Keine Berechtigung für diese Angebotsvorlage.'];
        }

        $updateData = ['updated_by' => $user->id];

        $fieldMap = [
            'name' => 'name',
            'description' => 'description',
            'weekday' => 'weekday',
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

        $template->update($updateData);

        if (array_key_exists('assigned_user_ids', $arguments)) {
            $template->assignedUsers()->sync($arguments['assigned_user_ids']);
        }

        $template->load(['rooms', 'times', 'colors', 'assignedUsers']);

        $weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

        return [
            'success' => true,
            'message' => 'Angebotsvorlage erfolgreich aktualisiert.',
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

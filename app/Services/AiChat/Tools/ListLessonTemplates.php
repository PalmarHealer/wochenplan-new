<?php

namespace App\Services\AiChat\Tools;

use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListLessonTemplates implements AiChatTool
{
    public function name(): string
    {
        return 'list_lesson_templates';
    }

    public function displayName(): string
    {
        return 'Angebotsvorlagen auflisten';
    }

    public function description(): string
    {
        return 'List lesson templates (Angebotsvorlagen / wiederkehrende Angebote) filtered by weekday. Templates define recurring lessons. Weekdays: 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'weekday' => [
                    'type' => 'integer',
                    'description' => 'Filter by weekday (1=Monday to 5=Friday)',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_lesson::template';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $query = LessonTemplate::with(['rooms', 'times', 'colors', 'assignedUsers']);

        if (! $user->can('view_any_lesson::template')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('user_id', $user->id));
        }

        if (! empty($arguments['weekday'])) {
            $query->where('weekday', $arguments['weekday']);
        }

        $templates = $query->orderBy('weekday')->limit(50)->get();

        $weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

        return [
            'count' => $templates->count(),
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'name' => strip_tags($t->name),
                'description' => strip_tags($t->description),
                'weekday' => $t->weekday,
                'weekday_name' => $weekdayNames[$t->weekday] ?? $t->weekday,
                'room' => $t->rooms?->name,
                'time' => $t->times?->name,
                'color' => $t->colors?->name,
                'disabled' => (bool) $t->disabled,
                'assigned_users' => $t->assignedUsers->pluck('display_name', 'id')->toArray(),
                'notes' => $t->notes,
            ])->toArray(),
        ];
    }
}

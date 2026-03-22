<?php

namespace App\Services\AiChat\Tools;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListLessons implements AiChatTool
{
    public function name(): string
    {
        return 'list_lessons';
    }

    public function displayName(): string
    {
        return 'Angebote auflisten';
    }

    public function description(): string
    {
        return 'List lessons (Angebote) filtered by date, room, time slot, or assigned user. Returns lesson details including name, date, room, time, color, and assigned users.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'Filter by date (YYYY-MM-DD format)',
                ],
                'room_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by room ID',
                ],
                'time_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by time slot ID',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by assigned user ID',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_lesson';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $query = Lesson::with(['rooms', 'times', 'colors', 'assignedUsers']);

        // Scope: only assigned lessons unless user has view_any_lesson
        if (! $user->can('view_any_lesson')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('user_id', $user->id));
        }

        if (! empty($arguments['date'])) {
            $query->whereDate('date', $arguments['date']);
        }

        if (! empty($arguments['room_id'])) {
            $query->where('room', $arguments['room_id']);
        }

        if (! empty($arguments['time_id'])) {
            $query->where('lesson_time', $arguments['time_id']);
        }

        if (! empty($arguments['user_id'])) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('user_id', $arguments['user_id']));
        }

        $lessons = $query->orderBy('date')->limit(50)->get();

        return [
            'count' => $lessons->count(),
            'lessons' => $lessons->map(fn ($l) => [
                'id' => $l->id,
                'name' => strip_tags($l->name),
                'description' => strip_tags($l->description),
                'date' => $l->date,
                'room' => $l->rooms?->name,
                'time' => $l->times?->name,
                'color' => $l->colors?->name,
                'disabled' => (bool) $l->disabled,
                'assigned_users' => $l->assignedUsers->pluck('display_name', 'id')->toArray(),
                'notes' => $l->notes,
            ])->toArray(),
        ];
    }
}

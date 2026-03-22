<?php

namespace App\Services\AiChat\Tools;

use App\Models\Absence;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;
use App\Services\LunchService;
use Carbon\Carbon;

class GetScheduleForDate implements AiChatTool
{
    public function name(): string
    {
        return 'get_schedule_for_date';
    }

    public function displayName(): string
    {
        return 'Tagesplan anzeigen';
    }

    public function description(): string
    {
        return 'Get the complete schedule for a specific date: all lessons, applicable templates, absences, and lunch. Provides a full day overview.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'The date to get the schedule for (YYYY-MM-DD format)',
                ],
            ],
            'required' => ['date'],
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
        $date = Carbon::parse($arguments['date']);
        $weekday = $date->format('N');

        // Get lessons for the date
        $lessonsQuery = Lesson::with(['rooms', 'times', 'colors', 'assignedUsers'])
            ->whereDate('date', $date);

        if (! $user->can('view_any_lesson')) {
            $lessonsQuery->whereHas('assignedUsers', fn ($q) => $q->where('user_id', $user->id));
        }

        $lessons = $lessonsQuery->get();
        $parentIds = $lessons->pluck('parent_id')->filter()->unique();

        // Get templates for the weekday (excluding those overridden by lessons)
        $templatesQuery = LessonTemplate::with(['rooms', 'times', 'colors', 'assignedUsers'])
            ->where('weekday', $weekday)
            ->where('created_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $date);
            });

        if (! $user->can('view_any_lesson::template')) {
            $templatesQuery->whereHas('assignedUsers', fn ($q) => $q->where('user_id', $user->id));
        }

        $templates = $templatesQuery->get()->reject(fn ($t) => $parentIds->contains($t->id));

        // Get absences
        $absences = Absence::with(['user'])
            ->whereDate('start', '<=', $date)
            ->whereDate('end', '>=', $date)
            ->get();

        // Get lunch
        $lunch = app(LunchService::class)->getLunch($date->toDateString());

        $weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

        return [
            'date' => $date->format('Y-m-d'),
            'weekday' => $weekdayNames[(int) $weekday] ?? $weekday,
            'lunch' => $lunch,
            'lessons' => $lessons->map(fn ($l) => [
                'id' => $l->id,
                'type' => 'lesson',
                'name' => strip_tags($l->name),
                'room' => $l->rooms?->name,
                'time' => $l->times?->name,
                'color' => $l->colors?->name,
                'disabled' => (bool) $l->disabled,
                'assigned_users' => $l->assignedUsers->pluck('display_name', 'id')->toArray(),
            ])->toArray(),
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'type' => 'template',
                'name' => strip_tags($t->name),
                'room' => $t->rooms?->name,
                'time' => $t->times?->name,
                'color' => $t->colors?->name,
                'disabled' => (bool) $t->disabled,
                'assigned_users' => $t->assignedUsers->pluck('display_name', 'id')->toArray(),
            ])->toArray(),
            'absences' => $absences->map(fn ($a) => [
                'id' => $a->id,
                'user' => $a->user?->display_name ?? $a->user?->name,
                'user_id' => $a->user_id,
            ])->toArray(),
        ];
    }
}
